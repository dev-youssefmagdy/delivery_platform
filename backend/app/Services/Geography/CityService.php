<?php

namespace App\Services\Geography;

use App\DTOs\Geography\CityData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\City;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * City rules: every write is transactional and audited, updates use optimistic locking. Cities start inactive;
 * deactivation needs a reason; a city that still has zones cannot be deleted; the country must be active.
 */
final class CityService
{
    public function __construct(
        private readonly CityRepositoryInterface $cities,
        private readonly CountryRepositoryInterface $countries,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(CityData $data, ?Authenticatable $actor): City
    {
        $this->assertCountryActive($data->countryId);

        return $this->db->transaction(function () use ($data, $actor): City {
            /** @var City $city */
            $city = $this->cities->create($data->attributes() + ['active' => false]);
            $this->audit->record(AuditAction::Created, $city, $actor, after: $city->auditSnapshot());

            return $city;
        });
    }

    public function update(City $city, CityData $data, ?Authenticatable $actor): City
    {
        $this->assertCountryActive($data->countryId);

        return $this->db->transaction(function () use ($city, $data, $actor): City {
            $locked = $this->lock($city);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $this->cities->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function activate(City $city, ?Authenticatable $actor): City
    {
        return $this->setActive($city, true, $actor, null);
    }

    public function deactivate(City $city, string $reason, ?Authenticatable $actor): City
    {
        return $this->setActive($city, false, $actor, $reason);
    }

    public function delete(City $city, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($city, $actor, $reason): void {
            $locked = $this->lock($city);

            if ($this->cities->zoneCount($locked) > 0) {
                throw BusinessRuleViolation::make('city_has_zones');
            }

            $before = $locked->auditSnapshot();
            $this->cities->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    private function setActive(City $city, bool $active, ?Authenticatable $actor, ?string $reason): City
    {
        return $this->db->transaction(function () use ($city, $active, $actor, $reason): City {
            $locked = $this->lock($city);

            if ($locked->active === $active) {
                return $locked;
            }

            if ($active) {
                $this->assertCountryActive($locked->country_id);
            }

            $this->cities->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);

            return $locked;
        });
    }

    private function assertCountryActive(int $countryId): void
    {
        $country = $this->countries->find($countryId);

        if ($country === null || ! $country->active) {
            throw BusinessRuleViolation::make('city_country_inactive', 'country_id');
        }
    }

    private function lock(City $city): City
    {
        $locked = $this->cities->lockById($city->getKey());

        return $locked instanceof City ? $locked : throw (new ModelNotFoundException)->setModel(City::class);
    }
}
