<?php

namespace App\Services\Catalog;

use App\DTOs\Catalog\ServiceCategoryData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Catalog\CatalogUsageRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryVersionRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFormSchemaRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Service category rules (Plan P1.4 steps 1 and 3). The row is the editable draft; clients only see published
 * versions. Categories start inactive and can be activated only once published. Deactivation is guarded: never
 * the last active category of the platform, and never with active bookings unless the admin explicitly
 * confirms (and always with a reason).
 */
final class ServiceCategoryService
{
    public function __construct(
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly ServiceFamilyRepositoryInterface $families,
        private readonly ServiceFormSchemaRepositoryInterface $schemas,
        private readonly ServiceCategoryVersionRepositoryInterface $versions,
        private readonly CatalogUsageRepositoryInterface $usage,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(ServiceCategoryData $data, ?Authenticatable $actor): ServiceCategory
    {
        if ($this->categories->codeExists($data->code)) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }

        try {
            return $this->db->transaction(function () use ($data, $actor): ServiceCategory {
                /** @var ServiceCategory $category */
                $category = $this->categories->create($data->attributes() + ['active' => false]);
                $this->schemas->createDraft($category, []);
                $this->audit->record(AuditAction::Created, $category, $actor, after: $category->auditSnapshot());

                return $category;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }
    }

    public function update(ServiceCategory $category, ServiceCategoryData $data, ?Authenticatable $actor): ServiceCategory
    {
        return $this->db->transaction(function () use ($category, $data, $actor): ServiceCategory {
            $locked = $this->lock($category);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($data->code !== $locked->code) {
                throw BusinessRuleViolation::make('catalog_code_immutable', 'code');
            }

            $before = $locked->auditSnapshot();
            $this->categories->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function activate(ServiceCategory $category, ?Authenticatable $actor): ServiceCategory
    {
        return $this->db->transaction(function () use ($category, $actor): ServiceCategory {
            $locked = $this->lock($category);

            if ($locked->active) {
                return $locked;
            }

            if ($locked->published_version_id === null) {
                throw BusinessRuleViolation::make('category_requires_published_version');
            }

            if (! $this->families->findOrFail($locked->service_family_id)->active) {
                throw BusinessRuleViolation::make('category_family_inactive');
            }

            $this->categories->update($locked, ['active' => true, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => false], ['active' => true]);

            return $locked;
        });
    }

    public function deactivate(ServiceCategory $category, string $reason, bool $confirmActiveBookings, ?Authenticatable $actor): ServiceCategory
    {
        return $this->db->transaction(function () use ($category, $reason, $confirmActiveBookings, $actor): ServiceCategory {
            $locked = $this->lock($category);

            if (! $locked->active) {
                return $locked;
            }

            if ($this->categories->activeCount(exceptId: $locked->id) === 0) {
                throw BusinessRuleViolation::make('catalog_last_active_category');
            }

            $activeBookings = $this->usage->activeBookingCount($locked);

            if ($activeBookings > 0 && ! $confirmActiveBookings) {
                throw BusinessRuleViolation::make('category_has_active_bookings', 'confirm_active_bookings', ['count' => $activeBookings]);
            }

            $this->categories->update($locked, ['active' => false, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(
                AuditAction::StatusChanged,
                $locked,
                $actor,
                ['active' => true],
                ['active' => false],
                $reason,
                ['active_bookings' => $activeBookings, 'confirmed_active_bookings' => $confirmActiveBookings],
            );

            return $locked;
        });
    }

    public function delete(ServiceCategory $category, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($category, $actor, $reason): void {
            $locked = $this->lock($category);

            if ($locked->active || $this->versions->countFor($locked) > 0) {
                throw BusinessRuleViolation::make('category_not_deletable');
            }

            $before = $locked->auditSnapshot();
            $locked->formSchemas()->delete();
            $this->categories->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    /**
     * @param list<int> $vehicleCategoryIds
     */
    public function syncVehicleCategories(ServiceCategory $category, array $vehicleCategoryIds, ?Authenticatable $actor): ServiceCategory
    {
        return $this->db->transaction(function () use ($category, $vehicleCategoryIds, $actor): ServiceCategory {
            $locked = $this->lock($category);
            $before = $this->categories->vehicleCategoryIds($locked);
            $this->categories->syncVehicleCategories($locked, array_values(array_unique($vehicleCategoryIds)));
            $after = $this->categories->vehicleCategoryIds($locked);

            if ($before !== $after) {
                $this->audit->record(AuditAction::Updated, $locked, $actor, ['vehicle_category_ids' => $before], ['vehicle_category_ids' => $after]);
            }

            return $locked;
        });
    }

    private function lock(ServiceCategory $category): ServiceCategory
    {
        $locked = $this->categories->lockById($category->getKey());

        return $locked instanceof ServiceCategory ? $locked : throw (new ModelNotFoundException)->setModel(ServiceCategory::class);
    }
}
