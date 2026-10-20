<?php

namespace App\Services\Provider;

use App\DTOs\Provider\ChecklistItem;
use App\DTOs\Provider\OnboardingProgress;
use App\DTOs\Provider\OnboardingTaskItem;
use App\Enums\Identity\ClientApp;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\OnboardingTask;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Enums\Settings\SettingKey;
use App\Models\PolicyVersion;
use App\Models\ProviderProfile;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Identity\ConsentService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;

/**
 * Computes where a driver stands in the application: which wizard steps are done, what is still to do (typed tasks the
 * app maps to screens), which required documents are missing, expiring or rejected, and whether the application can
 * be submitted. Always derived from the real data, never stored, so it cannot drift (SRS §7.1 step 8).
 */
final class OnboardingProgressService
{
    public const STEP_PROFILE = 'profile';

    public const STEP_SERVICES = 'services';

    public const STEP_VEHICLES = 'vehicles';

    public const STEP_DOCUMENTS = 'documents';

    public const STEP_DECLARATIONS = 'declarations';

    public const STEP_REVIEW = 'review';

    public function __construct(
        private readonly DocumentChecklistService $checklists,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderServiceRepositoryInterface $services,
        private readonly ConsentService $consents,
        private readonly SettingsService $settings,
    ) {}

    public function for(ProviderProfile $profile): OnboardingProgress
    {
        $profile->loadMissing('user', 'city');
        $vehicles = $this->vehicles->forProvider($profile)->filter(static fn (Vehicle $vehicle): bool => $vehicle->status !== VehicleStatus::Inactive)->values();
        $providerChecklist = $this->checklists->forProvider($profile);
        $vehicleChecklists = [];

        foreach ($vehicles as $vehicle) {
            $vehicle->setRelation('providerProfile', $profile);
            $vehicleChecklists[] = ['vehicle' => $vehicle, 'items' => $this->checklists->forVehicle($vehicle)];
        }

        $pending = $this->consents->status($profile->user, ClientApp::Provider)->pending;
        $pendingPolicyIds = array_map(static fn (PolicyVersion $policy): string => $policy->uuid, $pending);

        $tasks = [];
        $profileDone = $this->isProfileComplete($profile);
        $servicesDone = $this->services->categoryIds($profile, [ServiceApprovalStatus::Requested, ServiceApprovalStatus::Approved]) !== [];

        if (! $profileDone) {
            $tasks[] = new OnboardingTaskItem(OnboardingTask::CompleteProfile);
        }

        if (! $servicesDone) {
            $tasks[] = new OnboardingTaskItem(OnboardingTask::SelectServices);
        }

        $submitted = $vehicles->filter(static fn (Vehicle $vehicle): bool => in_array($vehicle->status, [VehicleStatus::UnderReview, VehicleStatus::Approved, VehicleStatus::Suspended], true));
        $vehiclesDone = $submitted->isNotEmpty();

        if ($vehicles->isEmpty()) {
            $tasks[] = new OnboardingTaskItem(OnboardingTask::AddVehicle);
        }

        foreach ($vehicles as $vehicle) {
            foreach ($this->vehicleTasks($vehicle, $submitted->isNotEmpty()) as $task) {
                $tasks[] = $task;
            }
        }

        $documentTasks = [];

        foreach ($providerChecklist as $item) {
            $documentTasks = [...$documentTasks, ...$this->documentTask($item, null)];
        }

        foreach ($vehicleChecklists as $entry) {
            // Vehicles still being prepared only matter while no other vehicle is already in the process.
            if (! $submitted->contains('id', $entry['vehicle']->id) && $submitted->isNotEmpty()) {
                continue;
            }

            foreach ($entry['items'] as $item) {
                $documentTasks = [...$documentTasks, ...$this->documentTask($item, $entry['vehicle']->uuid)];
            }
        }

        $tasks = [...$tasks, ...$documentTasks];
        $documentsDone = $documentTasks === [];

        if ($pendingPolicyIds !== []) {
            $tasks[] = new OnboardingTaskItem(OnboardingTask::AcceptPolicies);
        }

        $blocking = $tasks;
        $canSubmit = $profile->status->canSubmitForReview() && $blocking === [] && $vehiclesDone;

        if ($canSubmit) {
            $tasks[] = new OnboardingTaskItem(OnboardingTask::SubmitApplication);
        }

        $steps = [
            self::STEP_PROFILE => $profileDone,
            self::STEP_SERVICES => $servicesDone,
            self::STEP_VEHICLES => $vehiclesDone,
            self::STEP_DOCUMENTS => $documentsDone && $vehiclesDone,
            self::STEP_DECLARATIONS => $pendingPolicyIds === [],
        ];

        return new OnboardingProgress(
            profile: $profile,
            steps: $steps,
            resumeStep: $this->resumeStep($steps),
            tasks: $tasks,
            providerChecklist: $providerChecklist,
            vehicleChecklists: $vehicleChecklists,
            expiring: $this->expiring($providerChecklist, $vehicleChecklists),
            pendingPolicyIds: $pendingPolicyIds,
            support: [
                'phone' => $this->settings->string(SettingKey::SupportPhone),
                'email' => $this->settings->string(SettingKey::SupportEmail),
                'whatsapp' => $this->settings->string(SettingKey::SupportWhatsapp),
            ],
            canSubmit: $canSubmit,
            editable: $profile->status->isApplicationEditable(),
        );
    }

    public function isProfileComplete(ProviderProfile $profile): bool
    {
        $profile->loadMissing('user');

        return $profile->provider_type !== null
            && $profile->city_id !== null
            && $profile->user->isProfileComplete()
            && ($profile->provider_type === ProviderType::CompanyFleet || $profile->date_of_birth !== null);
    }

    /**
     * @param array<string, bool> $steps
     */
    private function resumeStep(array $steps): string
    {
        foreach ($steps as $step => $done) {
            if (! $done) {
                return $step;
            }
        }

        return self::STEP_REVIEW;
    }

    /**
     * @return list<OnboardingTaskItem>
     */
    private function vehicleTasks(Vehicle $vehicle, bool $hasSubmittedVehicle): array
    {
        $tasks = [];

        if ($vehicle->status === VehicleStatus::Rejected) {
            // A rejected vehicle only blocks when no other vehicle is in the process; otherwise it is optional to fix.
            if (! $hasSubmittedVehicle) {
                $tasks[] = new OnboardingTaskItem(OnboardingTask::FixVehicle, null, $vehicle->uuid);
            }

            return $tasks;
        }

        if ($vehicle->status !== VehicleStatus::Draft) {
            return [];
        }

        if ($hasSubmittedVehicle) {
            return [];
        }

        if ($vehicle->photos->count() < (int) config('provider.vehicles.min_photos', 2)) {
            $tasks[] = new OnboardingTaskItem(OnboardingTask::AddVehiclePhotos, null, $vehicle->uuid);
        }

        $tasks[] = new OnboardingTaskItem(OnboardingTask::SubmitVehicle, null, $vehicle->uuid);

        return $tasks;
    }

    /**
     * @return list<OnboardingTaskItem>
     */
    private function documentTask(ChecklistItem $item, ?string $vehicleUuid): array
    {
        if (! $item->mandatory || $item->isUploaded()) {
            return [];
        }

        $task = match ($item->state) {
            DocumentStatus::Rejected => OnboardingTask::ReplaceRejectedDocument,
            DocumentStatus::Expired => OnboardingTask::RenewExpiredDocument,
            default => OnboardingTask::UploadDocument,
        };

        return [new OnboardingTaskItem($task, $item->type->code, $vehicleUuid)];
    }

    /**
     * @param list<ChecklistItem> $providerChecklist
     * @param list<array{vehicle: Vehicle, items: list<ChecklistItem>}> $vehicleChecklists
     * @return list<ChecklistItem>
     */
    private function expiring(array $providerChecklist, array $vehicleChecklists): array
    {
        $limit = CarbonImmutable::today()->addDays($this->settings->int(SettingKey::ProviderDocumentExpiryWarningDays));
        $all = $providerChecklist;

        foreach ($vehicleChecklists as $entry) {
            $all = [...$all, ...$entry['items']];
        }

        return array_values(array_filter(
            $all,
            static fn (ChecklistItem $item): bool => $item->isSatisfied() && $item->expiresAt() !== null && $item->expiresAt()->lessThanOrEqualTo($limit),
        ));
    }
}
