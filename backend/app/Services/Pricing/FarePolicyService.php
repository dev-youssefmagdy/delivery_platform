<?php

namespace App\Services\Pricing;

use App\DTOs\Pricing\FarePolicyData;
use App\DTOs\Pricing\FarePolicyValues;
use App\DTOs\Pricing\PriceGuidance;
use App\Enums\Common\AuditAction;
use App\Enums\Pricing\SurchargeMode;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\FarePolicy;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Repositories\Contracts\Pricing\FarePolicyRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Admin configuration of fare policies (SRS §9.1, §9.3): audited writes, optimistic locking, one enabled policy per
 * scope (category + zone, or category-wide) at any moment, no deleting a policy that priced bookings. Editing a
 * policy never touches existing bookings: they hold their own snapshot (Plan I4). The reference formula has no
 * demand multiplier, so there is nothing to configure for one.
 */
final class FarePolicyService
{
    private const MAX_SURCHARGES = 20;

    private const MAX_SURCHARGE_MINOR = 10_000_000_000;

    public function __construct(
        private readonly FarePolicyRepositoryInterface $policies,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly ServiceZoneRepositoryInterface $zones,
        private readonly ReferencePriceCalculator $calculator,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(FarePolicyData $data, ?Admin $actor): FarePolicy
    {
        $this->assertValid($data);

        return $this->db->transaction(function () use ($data, $actor): FarePolicy {
            $this->assertNoOverlap($data->serviceCategoryId, $data->serviceZoneId, $data, null);

            /** @var FarePolicy $policy */
            $policy = $this->policies->create($data->attributes() + [
                'service_category_id' => $data->serviceCategoryId,
                'service_zone_id' => $data->serviceZoneId,
                'currency' => $data->currency,
                'active' => true,
            ]);
            $this->audit->record(AuditAction::Created, $policy, $actor, after: $policy->auditSnapshot());

            return $policy;
        });
    }

    public function update(FarePolicy $policy, FarePolicyData $data, ?Admin $actor): FarePolicy
    {
        $this->assertValid($data);

        return $this->db->transaction(function () use ($policy, $data, $actor): FarePolicy {
            $locked = $this->lock($policy);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($locked->active) {
                $this->assertNoOverlap($locked->service_category_id, $locked->service_zone_id, $data, $locked->id);
            }

            $before = $locked->auditSnapshot();
            $this->policies->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function setActive(FarePolicy $policy, bool $active, ?string $reason, ?Admin $actor): FarePolicy
    {
        return $this->db->transaction(function () use ($policy, $active, $reason, $actor): FarePolicy {
            $locked = $this->lock($policy);

            if ($locked->active === $active) {
                return $locked;
            }

            if ($active) {
                $this->assertNoOverlapWith($locked);
            }

            $this->policies->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);

            return $locked;
        });
    }

    public function delete(FarePolicy $policy, string $reason, ?Admin $actor): void
    {
        $this->db->transaction(function () use ($policy, $reason, $actor): void {
            $locked = $this->lock($policy);

            if ($this->policies->bookingCount($locked) > 0) {
                throw BusinessRuleViolation::make('fare_policy_in_use');
            }

            $before = $locked->auditSnapshot();
            $this->policies->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    /**
     * What the policy would recommend for a trip (the admin's "try it" calculator). Pure: nothing is stored.
     *
     * @param array<string, mixed> $serviceDetails
     */
    public function preview(FarePolicy $policy, int $distanceMeters, int $durationSeconds, int $stopCount, array $serviceDetails = []): PriceGuidance
    {
        return $this->calculator->calculate(FarePolicyValues::fromModel($policy), $distanceMeters, $durationSeconds, $stopCount, $serviceDetails, $policy->id);
    }

    private function assertValid(FarePolicyData $data): void
    {
        if ($this->categories->find($data->serviceCategoryId) === null) {
            throw BusinessRuleViolation::make('fare_policy_category_missing', 'service_category_id');
        }

        if ($data->serviceZoneId !== null && $this->zones->find($data->serviceZoneId) === null) {
            throw BusinessRuleViolation::make('fare_policy_zone_missing', 'service_zone_id');
        }

        if ($data->effectiveTo !== null && $data->effectiveTo->lessThanOrEqualTo($data->effectiveFrom)) {
            throw BusinessRuleViolation::make('fare_policy_window_invalid', 'effective_to');
        }

        if ($data->rangeLowBp < 1 || $data->rangeLowBp > 10_000) {
            throw BusinessRuleViolation::make('fare_policy_range_invalid', 'range_low_pct');
        }

        if ($data->rangeHighBp < 10_000 || $data->rangeHighBp > 1_000_000) {
            throw BusinessRuleViolation::make('fare_policy_range_invalid', 'range_high_pct');
        }

        if (count($data->surcharges) > self::MAX_SURCHARGES) {
            throw BusinessRuleViolation::make('fare_policy_surcharge_invalid', 'surcharges');
        }

        foreach ($data->surcharges as $index => $row) {
            if (preg_match('/^[a-z][a-z0-9_]{0,39}$/', $row['field']) !== 1
                || SurchargeMode::tryFrom($row['mode']) === null
                || $row['amount_minor'] <= 0
                || $row['amount_minor'] > self::MAX_SURCHARGE_MINOR) {
                throw BusinessRuleViolation::make('fare_policy_surcharge_invalid', "surcharges.{$index}.field");
            }
        }
    }

    private function assertNoOverlap(int $categoryId, ?int $zoneId, FarePolicyData $data, ?int $exceptId): void
    {
        $overlapping = $this->policies->overlapping($categoryId, $zoneId, $data->effectiveFrom, $data->effectiveTo, $exceptId);

        if ($overlapping !== []) {
            throw BusinessRuleViolation::make('fare_policy_overlap', 'effective_from', ['ids' => implode(', ', array_map(static fn (FarePolicy $policy): int => $policy->id, $overlapping))]);
        }
    }

    private function assertNoOverlapWith(FarePolicy $policy): void
    {
        $overlapping = $this->policies->overlapping($policy->service_category_id, $policy->service_zone_id, $policy->effective_from, $policy->effective_to, $policy->id);

        if ($overlapping !== []) {
            throw BusinessRuleViolation::make('fare_policy_overlap', 'effective_from');
        }
    }

    private function lock(FarePolicy $policy): FarePolicy
    {
        $locked = $this->policies->lockById($policy->id);

        return $locked instanceof FarePolicy ? $locked : throw (new ModelNotFoundException)->setModel(FarePolicy::class);
    }
}
