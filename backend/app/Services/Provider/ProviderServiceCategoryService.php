<?php

namespace App\Services\Provider;

use App\Enums\Common\AuditAction;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Per-service decisions of an officer on an already approved provider (the first decision is made when the
 * application is approved): approve a newly requested service or reject / revoke one, always with a reason and audited.
 */
final class ProviderServiceCategoryService
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly ProviderServiceRepositoryInterface $services,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `provider_service_not_found`, `provider_status_transition_invalid`
     */
    public function decide(ProviderProfile $profile, int $serviceCategoryId, ServiceApprovalStatus $decision, string $reason, Admin $actor): ProviderServiceCategory
    {
        return $this->db->transaction(function () use ($profile, $serviceCategoryId, $decision, $reason, $actor): ProviderServiceCategory {
            $locked = $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);

            if (! in_array($locked->status, [ProviderStatus::Approved, ProviderStatus::TemporarilySuspended], true)) {
                throw BusinessRuleViolation::make('provider_status_transition_invalid');
            }

            $service = $this->services->find($locked, $serviceCategoryId) ?? throw BusinessRuleViolation::make('provider_service_not_found');
            $before = ['status' => $service->status->value];
            $this->services->decide($service, $decision, $actor->id, $reason);
            $this->audit->record(
                $decision === ServiceApprovalStatus::Approved ? AuditAction::Approved : AuditAction::Rejected,
                $locked,
                $actor,
                $before,
                ['status' => $decision->value],
                $reason,
                ['service_category_id' => $serviceCategoryId],
            );

            return $service;
        });
    }
}
