<?php

namespace App\Services\Provider;

use App\DTOs\Provider\ChecklistItem;
use App\DTOs\Provider\EligibilityIssue;
use App\DTOs\Provider\EligibilityResult;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\EligibilityReason;
use App\Enums\Provider\EligibilityScope;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Enums\Settings\SettingKey;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\Vehicle;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Services\Booking\CancellationAbuseService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;

/**
 * The single answer to "may this provider work?" (Plan invariant I11). `online_eligible` and "expired documents" are
 * computed here from the real data on every call, never stored. The online toggle, dispatch and offer submission
 * (P2.2+) all call `check()`; the driver app shows its reasons on the verification screen today.
 *
 * Reasons come back as typed `EligibilityReason`s with a reference (document type code, vehicle uuid). With
 * `EligibilityScope::Working` (online toggle, dispatch, offer submission) the runtime conditions are added: a fresh
 * location, no conflicting active job (when the platform allows one job at a time), the cash debt limit and no running
 * cancellation cooldown (P3.5).
 */
final class ProviderEligibilityService
{
    public function __construct(
        private readonly DocumentChecklistService $checklists,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderServiceRepositoryInterface $services,
        private readonly ProviderLastLocationRepositoryInterface $locations,
        private readonly ProviderWorkloadRepositoryInterface $workload,
        private readonly SettingsService $settings,
        private readonly CancellationAbuseService $abuse,
    ) {}

    public function check(ProviderProfile $profile, ?ServiceCategory $category = null, EligibilityScope $scope = EligibilityScope::Onboarding): EligibilityResult
    {
        $profile->loadMissing('user');
        $issues = [];

        $blockCode = $profile->user->status->blockCode(strict: true);

        if ($blockCode !== null || $profile->user->trashed()) {
            $issues[] = new EligibilityIssue(EligibilityReason::AccountBlocked, $blockCode);
        }

        if ($profile->status->isSuspended()) {
            $issues[] = new EligibilityIssue(EligibilityReason::ProviderSuspended);
        } elseif ($profile->status !== ProviderStatus::Approved) {
            $issues[] = new EligibilityIssue(EligibilityReason::NotApproved);
        }

        foreach (DocumentChecklistService::unsatisfiedMandatory($this->checklists->forProvider($profile)) as $item) {
            $issues[] = $this->documentIssue($item);
        }

        [$usable, $vehicleIssues] = $this->vehicleState($profile);
        $issues = [...$issues, ...$vehicleIssues];

        if ($category !== null) {
            $approved = $this->services->find($profile, $category->id);

            if ($approved === null || $approved->status !== ServiceApprovalStatus::Approved) {
                $issues[] = new EligibilityIssue(EligibilityReason::ServiceNotApproved, (string) $category->id);
            }

            $linked = $category->vehicleCategories()->pluck('vehicle_categories.id')->all();
            $fits = array_filter($usable, static fn (Vehicle $vehicle): bool => in_array($vehicle->vehicle_category_id, $linked, true));

            if ($usable !== [] && $fits === []) {
                $issues[] = new EligibilityIssue(EligibilityReason::VehicleCategoryMismatch, (string) $category->id);
            }

            $usable = array_values($fits);
        }

        if ($scope === EligibilityScope::Working) {
            $issues = [...$issues, ...$this->runtimeIssues($profile)];
        }

        return new EligibilityResult($issues, array_map(static fn (Vehicle $vehicle): int => $vehicle->id, $usable));
    }

    /**
     * @return list<EligibilityIssue>
     */
    private function runtimeIssues(ProviderProfile $profile): array
    {
        $issues = [];
        $last = $this->locations->findFor($profile);
        $staleAfter = $this->settings->int(SettingKey::LocationStaleAfterSeconds);

        if ($last === null || CarbonImmutable::instance($last->recorded_at)->lessThan(now()->subSeconds($staleAfter))) {
            $issues[] = new EligibilityIssue(EligibilityReason::LocationStale, $last === null ? 'missing' : 'stale');
        }

        if ($this->settings->bool(SettingKey::ProviderSingleActiveJob) && $this->workload->activeBooking($profile) !== null) {
            $issues[] = new EligibilityIssue(EligibilityReason::ActiveJobExists);
        }

        if ($this->workload->cashDebtMinor($profile) > $this->settings->int(SettingKey::ProviderCashDebtLimitMinor)) {
            $issues[] = new EligibilityIssue(EligibilityReason::CashDebtLimitExceeded);
        }

        $cooldownUntil = $this->abuse->providerCooldownUntil($profile);

        if ($cooldownUntil !== null) {
            $issues[] = new EligibilityIssue(EligibilityReason::CancellationCooldown, $cooldownUntil->toIso8601ZuluString());
        }

        return $issues;
    }

    /**
     * Approved vehicles whose required documents are all valid.
     *
     * @return array{0: list<Vehicle>, 1: list<EligibilityIssue>}
     */
    private function vehicleState(ProviderProfile $profile): array
    {
        $approved = $this->vehicles->forProvider($profile)->filter(static fn (Vehicle $vehicle): bool => $vehicle->status === VehicleStatus::Approved);

        if ($approved->isEmpty()) {
            return [[], [new EligibilityIssue(EligibilityReason::NoApprovedVehicle)]];
        }

        $usable = [];
        $issues = [];

        foreach ($approved as $vehicle) {
            $vehicle->setRelation('providerProfile', $profile);
            $missing = DocumentChecklistService::unsatisfiedMandatory($this->checklists->forVehicle($vehicle));

            if ($missing === []) {
                $usable[] = $vehicle;

                continue;
            }

            foreach ($missing as $item) {
                $issue = $this->documentIssue($item);
                $issues[] = new EligibilityIssue($issue->reason, $vehicle->uuid.':'.$issue->reference);
            }
        }

        // A vehicle that is approved but has an invalid required document contributes its document reasons; the
        // vehicles that are fully valid make the provider usable, so their siblings' problems are not reported.
        return [$usable, $usable === [] ? $issues : []];
    }

    private function documentIssue(ChecklistItem $item): EligibilityIssue
    {
        $reason = match ($item->state) {
            null => EligibilityReason::DocumentMissing,
            DocumentStatus::Expired => EligibilityReason::DocumentExpired,
            default => EligibilityReason::DocumentNotApproved,
        };

        return new EligibilityIssue($reason, $item->type->code);
    }
}
