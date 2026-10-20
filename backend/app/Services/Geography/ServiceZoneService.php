<?php

namespace App\Services\Geography;

use App\DTOs\Geography\ServiceZoneData;
use App\DTOs\Geography\ZoneAvailabilityData;
use App\DTOs\Geography\ZoneSaveResult;
use App\Enums\Common\AuditAction;
use App\Enums\Geo\ZoneStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\ServiceZone;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Repositories\Contracts\Geography\ZoneServiceCategoryRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Geo\GeoPolygon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service zone rules. Zones start as drafts; a zone goes live only with a valid polygon and at least one
 * enabled category; suspending needs a reason; an active zone cannot be deleted. Polygon overlap with other
 * zones is reported as a warning (resolution then picks the smallest zone), never blocked. Drawing a polygon is
 * not legal authorization to operate there; that stays an operations decision.
 */
final class ServiceZoneService
{
    public function __construct(
        private readonly ServiceZoneRepositoryInterface $zones,
        private readonly ZoneServiceCategoryRepositoryInterface $links,
        private readonly CityRepositoryInterface $cities,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(ServiceZoneData $data, ?Authenticatable $actor): ZoneSaveResult
    {
        $this->assertCity($data->cityId);
        $this->assertPolygon($data->polygon);

        return $this->db->transaction(function () use ($data, $actor): ZoneSaveResult {
            /** @var ServiceZone $zone */
            $zone = $this->zones->create($data->attributes() + ['status' => ZoneStatus::Draft]);
            $overlaps = $this->zones->overlapping($data->polygon, $zone->id);
            $this->audit->record(AuditAction::Created, $zone, $actor, after: $zone->auditSnapshot(), meta: $this->overlapMeta($overlaps));

            return new ZoneSaveResult($zone, $overlaps);
        });
    }

    public function update(ServiceZone $zone, ServiceZoneData $data, ?Authenticatable $actor): ZoneSaveResult
    {
        $this->assertCity($data->cityId);
        $this->assertPolygon($data->polygon);

        return $this->db->transaction(function () use ($zone, $data, $actor): ZoneSaveResult {
            $locked = $this->lock($zone);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->zones->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $overlaps = $this->zones->overlapping($data->polygon, $locked->id);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return new ZoneSaveResult($locked, $overlaps);
        });
    }

    public function activate(ServiceZone $zone, ?Authenticatable $actor): ServiceZone
    {
        return $this->db->transaction(function () use ($zone, $actor): ServiceZone {
            $locked = $this->lock($zone);

            if ($locked->status === ZoneStatus::Active) {
                return $locked;
            }

            if ($this->links->enabledCount($locked->id) === 0) {
                throw BusinessRuleViolation::make('zone_no_categories');
            }

            return $this->setStatus($locked, ZoneStatus::Active, $actor, null);
        });
    }

    public function suspend(ServiceZone $zone, string $reason, ?Authenticatable $actor): ServiceZone
    {
        return $this->db->transaction(function () use ($zone, $reason, $actor): ServiceZone {
            $locked = $this->lock($zone);

            if ($locked->status !== ZoneStatus::Active) {
                throw BusinessRuleViolation::make('zone_not_active');
            }

            return $this->setStatus($locked, ZoneStatus::Suspended, $actor, $reason);
        });
    }

    public function delete(ServiceZone $zone, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($zone, $actor, $reason): void {
            $locked = $this->lock($zone);

            if ($locked->status === ZoneStatus::Active) {
                throw BusinessRuleViolation::make('zone_active_cannot_delete');
            }

            $before = $locked->auditSnapshot();
            $this->zones->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    /**
     * Replaces the zone's category availability matrix.
     */
    public function updateAvailability(ServiceZone $zone, ZoneAvailabilityData $data, ?Authenticatable $actor): ServiceZone
    {
        $ids = array_map(static fn ($row): int => $row->serviceCategoryId, $data->rows);

        if (count($ids) !== count(array_unique($ids))) {
            throw BusinessRuleViolation::make('zone_availability_invalid', 'categories');
        }

        if ($this->categories->findManyByIds($ids)->count() !== count($ids)) {
            throw BusinessRuleViolation::make('zone_availability_invalid', 'categories');
        }

        foreach ($data->rows as $row) {
            if ($row->minFareMinor !== null && $row->maxFareMinor !== null && $row->minFareMinor > $row->maxFareMinor) {
                throw BusinessRuleViolation::make('zone_override_fare_range', "categories.{$row->serviceCategoryId}.max_fare_minor");
            }

            if ($row->radiusStepsKm !== [] && ($row->radiusStepsKm !== collect($row->radiusStepsKm)->sort()->values()->all() || min($row->radiusStepsKm) <= 0 || max($row->radiusStepsKm) > 500)) {
                throw BusinessRuleViolation::make('zone_override_radius', "categories.{$row->serviceCategoryId}.dispatch_radius_steps_km");
            }
        }

        return $this->db->transaction(function () use ($zone, $data, $actor): ServiceZone {
            $locked = $this->lock($zone);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $enabled = count(array_filter($data->rows, static fn ($row): bool => $row->active));

            if ($locked->status === ZoneStatus::Active && $enabled === 0) {
                throw BusinessRuleViolation::make('zone_no_categories');
            }

            $before = $this->matrix($locked);
            $this->links->replaceForZone($locked, $data->rows);
            $this->zones->update($locked, ['lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::Updated, $locked, $actor, ['availability' => $before], ['availability' => $this->matrix($locked)]);

            return $locked;
        });
    }

    /**
     * Other zones whose area overlaps `$polygon` (warning shown in the editor).
     *
     * @return Collection<int, ServiceZone>
     */
    public function overlaps(GeoPolygon $polygon, ?int $exceptZoneId = null): Collection
    {
        return $this->zones->overlapping($polygon, $exceptZoneId);
    }

    private function setStatus(ServiceZone $zone, ZoneStatus $status, ?Authenticatable $actor, ?string $reason): ServiceZone
    {
        $from = $zone->status;
        $this->zones->update($zone, ['status' => $status, 'lock_version' => $zone->lock_version + 1]);
        $this->audit->record(AuditAction::StatusChanged, $zone, $actor, ['status' => $from->value], ['status' => $status->value], $reason);

        return $zone;
    }

    /**
     * @return array<string, array{active: bool, overrides: array<string, mixed>|null}>
     */
    private function matrix(ServiceZone $zone): array
    {
        $out = [];

        foreach ($this->links->forZone($zone->id) as $link) {
            $out[$link->category->code] = ['active' => $link->active, 'overrides' => $link->overrides];
        }

        ksort($out);

        return $out;
    }

    private function assertCity(int $cityId): void
    {
        if ($this->cities->find($cityId) === null) {
            throw BusinessRuleViolation::make('zone_city_missing', 'city_id');
        }
    }

    private function assertPolygon(GeoPolygon $polygon): void
    {
        if (! $this->zones->isValidPolygon($polygon)) {
            throw BusinessRuleViolation::make('zone_invalid_polygon', 'polygon');
        }
    }

    /**
     * @param Collection<int, ServiceZone> $overlaps
     * @return array<string, mixed>
     */
    private function overlapMeta(Collection $overlaps): array
    {
        return $overlaps->isEmpty() ? [] : ['overlaps_zone_ids' => $overlaps->pluck('id')->all()];
    }

    private function lock(ServiceZone $zone): ServiceZone
    {
        $locked = $this->zones->lockById($zone->getKey());

        return $locked instanceof ServiceZone ? $locked : throw (new ModelNotFoundException)->setModel(ServiceZone::class);
    }
}
