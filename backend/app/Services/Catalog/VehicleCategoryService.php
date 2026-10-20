<?php

namespace App\Services\Catalog;

use App\DTOs\Catalog\VehicleCategoryData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\VehicleCategory;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Vehicle category rules: audited writes, optimistic locking, immutable code, no deletion while a service
 * category links the vehicle (deactivate it instead).
 */
final class VehicleCategoryService
{
    public function __construct(
        private readonly VehicleCategoryRepositoryInterface $vehicleCategories,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(VehicleCategoryData $data, ?Authenticatable $actor): VehicleCategory
    {
        if ($this->vehicleCategories->codeExists($data->code)) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }

        try {
            return $this->db->transaction(function () use ($data, $actor): VehicleCategory {
                /** @var VehicleCategory $vehicle */
                $vehicle = $this->vehicleCategories->create($data->attributes() + ['active' => false]);
                $this->audit->record(AuditAction::Created, $vehicle, $actor, after: $vehicle->auditSnapshot());

                return $vehicle;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }
    }

    public function update(VehicleCategory $vehicle, VehicleCategoryData $data, ?Authenticatable $actor): VehicleCategory
    {
        return $this->db->transaction(function () use ($vehicle, $data, $actor): VehicleCategory {
            $locked = $this->lock($vehicle);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($data->code !== $locked->code) {
                throw BusinessRuleViolation::make('catalog_code_immutable', 'code');
            }

            $before = $locked->auditSnapshot();
            $this->vehicleCategories->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function activate(VehicleCategory $vehicle, ?Authenticatable $actor): VehicleCategory
    {
        return $this->setActive($vehicle, true, $actor, null);
    }

    public function deactivate(VehicleCategory $vehicle, string $reason, ?Authenticatable $actor): VehicleCategory
    {
        return $this->setActive($vehicle, false, $actor, $reason);
    }

    public function delete(VehicleCategory $vehicle, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($vehicle, $actor, $reason): void {
            $locked = $this->lock($vehicle);

            if ($this->vehicleCategories->serviceCategoryCount($locked) > 0) {
                throw BusinessRuleViolation::make('vehicle_category_in_use');
            }

            $before = $locked->auditSnapshot();
            $this->vehicleCategories->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    private function setActive(VehicleCategory $vehicle, bool $active, ?Authenticatable $actor, ?string $reason): VehicleCategory
    {
        return $this->db->transaction(function () use ($vehicle, $active, $actor, $reason): VehicleCategory {
            $locked = $this->lock($vehicle);

            if ($locked->active !== $active) {
                $this->vehicleCategories->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
                $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);
            }

            return $locked;
        });
    }

    private function lock(VehicleCategory $vehicle): VehicleCategory
    {
        $locked = $this->vehicleCategories->lockById($vehicle->getKey());

        return $locked instanceof VehicleCategory ? $locked : throw (new ModelNotFoundException)->setModel(VehicleCategory::class);
    }
}
