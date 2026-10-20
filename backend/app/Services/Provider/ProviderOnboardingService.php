<?php

namespace App\Services\Provider;

use App\DTOs\Provider\ApproveApplicationData;
use App\DTOs\Provider\MoreInformationData;
use App\DTOs\Provider\OnboardingProgress;
use App\DTOs\Provider\ProviderApplicationData;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Exceptions\ApplicationIncompleteException;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The provider application lifecycle (Plan §7.3): the applicant starts and edits it, submits it for review, and an
 * officer approves, asks for more information or rejects it. Every status change goes through `ProviderStatusService`;
 * guards live here. Admin decisions need the row lock, re-check the guards and write the audit entry with the reason.
 */
final class ProviderOnboardingService
{
    public function __construct(
        private readonly ProviderProfileRepositoryInterface $profiles,
        private readonly UserRepositoryInterface $users,
        private readonly ProviderServiceRepositoryInterface $services,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly CityRepositoryInterface $cities,
        private readonly ProviderStatusService $status,
        private readonly OnboardingProgressService $progress,
        private readonly DocumentChecklistService $checklists,
        private readonly DocumentService $documents,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function profileOf(User $user): ?ProviderProfile
    {
        return $this->profiles->findByUserId($user->id);
    }

    /**
     * @return list<\App\Models\ProviderServiceCategory>
     */
    public function servicesOf(ProviderProfile $profile): array
    {
        return $this->services->forProvider($profile)->all();
    }

    public function progressOf(ProviderProfile $profile): OnboardingProgress
    {
        return $this->progress->for($profile);
    }

    /**
     * Creates the application of a phone-verified user: draft → phone_verified → profile_submitted → documents_pending.
     *
     * @throws ConflictException `application_exists`
     * @throws BusinessRuleViolation
     */
    public function start(User $user, ProviderApplicationData $data): ProviderProfile
    {
        $this->assertStartable($data);

        return $this->db->transaction(function () use ($user, $data): ProviderProfile {
            $this->users->lockById($user->id);

            if ($this->profiles->findByUserId($user->id) !== null) {
                throw ConflictException::make('application_exists');
            }

            $profile = $this->profiles->createFor($user);
            $this->status->recordCreation($profile, $user);
            $this->status->transition($profile, ProviderStatus::PhoneVerified, $user);

            $this->applyProfileFields($profile, $user, $data, true);

            if ($data->serviceCategoryIds !== null) {
                $this->syncServices($profile, $data->serviceCategoryIds);
            }

            $this->status->transition($profile, ProviderStatus::ProfileSubmitted, $user);
            $this->status->transition($profile, ProviderStatus::DocumentsPending, $user);

            return $profile->refresh();
        });
    }

    /**
     * Edits the application. Profile fields and services are editable until the application is under review; an
     * approved provider can still change the services it offers (new ones start as `requested`).
     *
     * @throws BusinessRuleViolation `application_locked`
     */
    public function update(User $user, ProviderApplicationData $data): ProviderProfile
    {
        return $this->db->transaction(function () use ($user, $data): ProviderProfile {
            $profile = $this->lockOwn($user);
            $editable = $profile->status->isApplicationEditable();
            $servicesOnly = $profile->status === ProviderStatus::Approved
                && $data->providerType === null && $data->cityId === null && $data->name === null && $data->dateOfBirth === null;

            if (! $editable && ! $servicesOnly) {
                throw BusinessRuleViolation::make('application_locked');
            }

            if ($data->providerType !== null || $data->cityId !== null || $data->name !== null || $data->dateOfBirth !== null) {
                $this->applyProfileFields($profile, $user, $data, false);
            }

            if ($data->serviceCategoryIds !== null) {
                $this->syncServices($profile, $data->serviceCategoryIds);
            }

            return $profile->refresh();
        });
    }

    /**
     * Submit (or resubmit after "more information" / rejection) for review.
     *
     * @throws ApplicationIncompleteException `application_incomplete`
     * @throws BusinessRuleViolation `application_locked`
     */
    public function submit(User $user): ProviderProfile
    {
        return $this->db->transaction(function () use ($user): ProviderProfile {
            $profile = $this->lockOwn($user);

            if (! $profile->status->canSubmitForReview()) {
                throw BusinessRuleViolation::make('application_locked');
            }

            $progress = $this->progress->for($profile);

            if (! $progress->canSubmit) {
                throw ApplicationIncompleteException::forTasks('application_incomplete', array_values(array_filter(
                    $progress->tasks,
                    static fn ($item): bool => $item->task->value !== 'submit_application',
                )));
            }

            $this->status->transition($profile, ProviderStatus::UnderReview, $user, meta: ['resubmission' => $profile->status !== ProviderStatus::DocumentsPending]);

            return $profile->refresh();
        });
    }

    public function assignToMe(ProviderProfile $profile, Admin $actor): ProviderProfile
    {
        return $this->db->transaction(function () use ($profile, $actor): ProviderProfile {
            $locked = $this->lock($profile);

            if (! $locked->status->isAwaitingReview()) {
                throw BusinessRuleViolation::make('provider_not_in_queue');
            }

            $before = ['assigned_admin_id' => $locked->assigned_admin_id];
            $this->profiles->assignTo($locked, $actor->id);
            $this->audit->record(AuditAction::Updated, $locked, $actor, $before, ['assigned_admin_id' => $actor->id], meta: ['action' => 'assigned']);

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `provider_status_transition_invalid`, `provider_documents_incomplete`, `provider_services_invalid`
     */
    public function approve(ProviderProfile $profile, ApproveApplicationData $data, Admin $actor): ProviderProfile
    {
        return $this->db->transaction(function () use ($profile, $data, $actor): ProviderProfile {
            $locked = $this->lock($profile);

            if ($locked->status !== ProviderStatus::UnderReview) {
                throw BusinessRuleViolation::make('provider_status_transition_invalid');
            }

            if (DocumentChecklistService::unsatisfiedMandatory($this->checklists->forProvider($locked)) !== []) {
                throw BusinessRuleViolation::make('provider_documents_incomplete');
            }

            $requested = $this->services->forProvider($locked)->filter(static fn ($service): bool => $service->status !== ServiceApprovalStatus::Rejected);
            $requestedIds = $requested->pluck('service_category_id')->map(static fn (mixed $id): int => (int) $id)->all();
            $approvedIds = array_values(array_unique($data->approvedServiceCategoryIds));

            if ($approvedIds === [] || array_diff($approvedIds, $requestedIds) !== []) {
                throw BusinessRuleViolation::make('provider_services_invalid', 'approved_services');
            }

            foreach ($requested as $service) {
                $isApproved = in_array($service->service_category_id, $approvedIds, true);
                $this->services->decide($service, $isApproved ? ServiceApprovalStatus::Approved : ServiceApprovalStatus::Rejected, $actor->id, $isApproved ? null : $data->note);
            }

            $from = $locked->status->value;
            $this->status->transition($locked, ProviderStatus::Approved, $actor, $data->note, meta: ['approved_services' => $approvedIds]);
            $this->audit->record(AuditAction::Approved, $locked, $actor, ['status' => $from], ['status' => ProviderStatus::Approved->value], $data->note, ['approved_services' => $approvedIds]);

            return $locked;
        });
    }

    /**
     * Sends the application back: rejects the listed pending documents with their own reasons and moves the provider
     * to `more_information_required` with the overall message.
     *
     * @throws BusinessRuleViolation `provider_status_transition_invalid`, `document_not_reviewable`
     */
    public function requestMoreInformation(ProviderProfile $profile, MoreInformationData $data, Admin $actor): ProviderProfile
    {
        return $this->db->transaction(function () use ($profile, $data, $actor): ProviderProfile {
            $locked = $this->lock($profile);

            if ($locked->status !== ProviderStatus::UnderReview) {
                throw BusinessRuleViolation::make('provider_status_transition_invalid');
            }

            foreach ($data->documentIssues as $uuid => $reason) {
                $document = $this->documents->findOwned($locked, (string) $uuid) ?? throw BusinessRuleViolation::make('document_not_reviewable', 'document_issues');
                $this->documents->reject($document, $reason, $actor);
            }

            $this->status->transition($locked, ProviderStatus::MoreInformationRequired, $actor, $data->reason, meta: ['documents' => array_keys($data->documentIssues)]);
            $this->audit->record(AuditAction::MoreInfoRequested, $locked, $actor, ['status' => ProviderStatus::UnderReview->value], ['status' => ProviderStatus::MoreInformationRequired->value], $data->reason, ['documents' => array_keys($data->documentIssues)]);

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `provider_status_transition_invalid`
     */
    public function reject(ProviderProfile $profile, string $reason, Admin $actor): ProviderProfile
    {
        return $this->db->transaction(function () use ($profile, $reason, $actor): ProviderProfile {
            $locked = $this->lock($profile);
            $from = $locked->status;

            $this->status->transition($locked, ProviderStatus::Rejected, $actor, $reason);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, ['status' => $from->value], ['status' => ProviderStatus::Rejected->value], $reason);

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation
     */
    private function assertStartable(ProviderApplicationData $data): void
    {
        if ($data->providerType === null || $data->cityId === null || $data->name === null || trim($data->name) === '') {
            throw BusinessRuleViolation::make('application_profile_incomplete');
        }

        if ($data->providerType !== ProviderType::CompanyFleet && $data->dateOfBirth === null) {
            throw BusinessRuleViolation::make('provider_birth_date_required', 'date_of_birth');
        }
    }

    private function applyProfileFields(ProviderProfile $profile, User $user, ProviderApplicationData $data, bool $creating): void
    {
        $attributes = [];

        if ($data->providerType !== null) {
            $attributes['provider_type'] = $data->providerType;
        }

        if ($data->cityId !== null) {
            $city = $this->cities->find($data->cityId);

            if ($city === null || ! $city->active) {
                throw BusinessRuleViolation::make('provider_city_unavailable', 'city_id');
            }

            $attributes['city_id'] = $city->id;
        }

        if ($data->dateOfBirth !== null) {
            $minimumAge = (int) config('provider.minimum_age', 18);

            if ($data->dateOfBirth->greaterThan(CarbonImmutable::today()->subYears($minimumAge))) {
                throw BusinessRuleViolation::make('provider_underage', 'date_of_birth', ['age' => $minimumAge]);
            }

            $attributes['date_of_birth'] = $data->dateOfBirth->toDateString();
        }

        if ($attributes !== []) {
            $this->profiles->updateApplication($profile, $attributes);
        }

        if ($data->name !== null && trim($data->name) !== '') {
            $this->users->update($user, ['name' => mb_substr(trim($data->name), 0, 100)]);
        }
    }

    /**
     * Makes the provider's requested categories equal `$ids`: new ones start as `requested`, a previously rejected one
     * is requested again, removed ones are dropped. Approved ones that stay keep their approval.
     *
     * @param list<int> $ids
     */
    private function syncServices(ProviderProfile $profile, array $ids): void
    {
        $ids = array_values(array_unique($ids));
        $categories = $this->categories->findManyByIds($ids);

        foreach ($ids as $id) {
            $category = $categories->first(static fn (ServiceCategory $candidate): bool => $candidate->id === $id);

            if ($category === null || ! $category->active || $category->published_version_id === null) {
                throw BusinessRuleViolation::make('provider_service_unavailable', 'service_category_ids');
            }
        }

        $existing = $this->services->forProvider($profile)->keyBy('service_category_id');

        foreach ($ids as $id) {
            $current = $existing->get($id);

            if ($current === null) {
                $this->services->request($profile, $id);
            } elseif ($current->status === ServiceApprovalStatus::Rejected) {
                $this->services->decide($current, ServiceApprovalStatus::Requested, null, null);
            }
        }

        foreach ($existing->keys() as $existingId) {
            if (! in_array((int) $existingId, $ids, true)) {
                $this->services->remove($profile, (int) $existingId);
            }
        }
    }

    private function lockOwn(User $user): ProviderProfile
    {
        $profile = $this->profiles->findByUserId($user->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);

        return $this->lock($profile);
    }

    private function lock(ProviderProfile $profile): ProviderProfile
    {
        return $this->profiles->lockForUpdate($profile->id) ?? throw (new ModelNotFoundException)->setModel(ProviderProfile::class);
    }
}
