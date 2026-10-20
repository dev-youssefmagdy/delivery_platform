<?php

namespace App\Services\Provider;

use App\DTOs\Provider\ChecklistItem;
use App\DTOs\Provider\EligibilityIssue;
use App\DTOs\Provider\EligibilityResult;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\EligibilityReason;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\Vehicle;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;

/**
 * The single answer to "may this provider work?" (Plan invariant I11). `online_eligible` and "expired documents" are
 * computed here from the real data on every call, never stored. The online toggle, dispatch and offer submission
 * (P2.2+) all call `check()`; the driver app shows its reasons on the verification screen today.
 *
 * Reasons come back as typed `EligibilityReason`s with a reference (document type code, vehicle uuid). P2.2 adds the
 * location, active-job and debt-limit reasons here.
 */
final class ProviderEligibilityService
{
    public function __construct(
        private readonly DocumentChecklistService $checklists,
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly ProviderServiceRepositoryInterface $services,
    ) {}

    public function check(ProviderProfile $profile, ?ServiceCategory $category = null): EligibilityResult
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

        return new EligibilityResult($issues, array_map(static fn (Vehicle $vehicle): int => $vehicle->id, $usable));
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
