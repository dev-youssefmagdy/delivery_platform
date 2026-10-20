<?php

namespace App\Services\Catalog;

use App\DTOs\Catalog\ServiceFamilyData;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\ServiceFamily;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Service family rules: audited transactional writes with optimistic locking. Families start inactive; the
 * code is immutable; a family with categories cannot be deleted; deactivating the family that holds the last
 * active categories of the platform is refused.
 */
final class ServiceFamilyService
{
    public function __construct(
        private readonly ServiceFamilyRepositoryInterface $families,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(ServiceFamilyData $data, ?Authenticatable $actor): ServiceFamily
    {
        if ($this->families->codeExists($data->code)) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }

        try {
            return $this->db->transaction(function () use ($data, $actor): ServiceFamily {
                /** @var ServiceFamily $family */
                $family = $this->families->create($data->attributes() + ['active' => false]);
                $this->audit->record(AuditAction::Created, $family, $actor, after: $family->auditSnapshot());

                return $family;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('catalog_code_taken', 'code');
        }
    }

    public function update(ServiceFamily $family, ServiceFamilyData $data, ?Authenticatable $actor): ServiceFamily
    {
        return $this->db->transaction(function () use ($family, $data, $actor): ServiceFamily {
            $locked = $this->lock($family);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($data->code !== $locked->code) {
                throw BusinessRuleViolation::make('catalog_code_immutable', 'code');
            }

            $before = $locked->auditSnapshot();
            $this->families->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function activate(ServiceFamily $family, ?Authenticatable $actor): ServiceFamily
    {
        return $this->setActive($family, true, $actor, null);
    }

    public function deactivate(ServiceFamily $family, string $reason, ?Authenticatable $actor): ServiceFamily
    {
        return $this->setActive($family, false, $actor, $reason);
    }

    public function delete(ServiceFamily $family, ?Authenticatable $actor, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($family, $actor, $reason): void {
            $locked = $this->lock($family);

            if ($this->families->categoryCount($locked) > 0) {
                throw BusinessRuleViolation::make('family_has_categories');
            }

            $before = $locked->auditSnapshot();
            $this->families->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    private function setActive(ServiceFamily $family, bool $active, ?Authenticatable $actor, ?string $reason): ServiceFamily
    {
        return $this->db->transaction(function () use ($family, $active, $actor, $reason): ServiceFamily {
            $locked = $this->lock($family);

            if ($locked->active === $active) {
                return $locked;
            }

            if (! $active && $this->families->activeCategoryCount($locked) > 0 && $this->categories->activeCount(exceptFamilyId: $locked->id) === 0) {
                throw BusinessRuleViolation::make('catalog_last_active_category');
            }

            $this->families->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(
                AuditAction::StatusChanged,
                $locked,
                $actor,
                ['active' => ! $active],
                ['active' => $active],
                $reason,
            );

            return $locked;
        });
    }

    private function lock(ServiceFamily $family): ServiceFamily
    {
        $locked = $this->families->lockById($family->getKey());

        return $locked instanceof ServiceFamily ? $locked : throw (new ModelNotFoundException)->setModel(ServiceFamily::class);
    }
}
