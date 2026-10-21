<?php

namespace App\Services\Booking;

use App\DTOs\Booking\CancellationPolicyData;
use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Booking\CancellationFeeType;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\CancellationPolicy;
use App\Repositories\Contracts\Booking\CancellationPolicyRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Admin configuration of cancellation policies (SRS §8.4): audited writes, optimistic locking, one enabled policy per
 * scope (category + zone, category-wide, or market default) at any moment, and no deleting a policy that governed
 * bookings. Editing a policy never touches existing bookings: they hold their own snapshot (Plan I4). A fee rule is
 * one per actor and stage; searching never carries a fee, so it cannot be written.
 */
final class CancellationPolicyService
{
    private const MAX_FEE_MINOR = 10_000_000_000;

    private const MAX_SECONDS = 86_400;

    public function __construct(
        private readonly CancellationPolicyRepositoryInterface $policies,
        private readonly ServiceCategoryRepositoryInterface $categories,
        private readonly ServiceZoneRepositoryInterface $zones,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(CancellationPolicyData $data, ?Admin $actor): CancellationPolicy
    {
        $this->assertValid($data, true);

        return $this->db->transaction(function () use ($data, $actor): CancellationPolicy {
            $this->assertNoOverlap($data->serviceCategoryId, $data->serviceZoneId, $data, null);

            /** @var CancellationPolicy $policy */
            $policy = $this->policies->create($data->attributes() + [
                'service_category_id' => $data->serviceCategoryId,
                'service_zone_id' => $data->serviceZoneId,
                'active' => true,
            ]);
            $this->audit->record(AuditAction::Created, $policy, $actor, after: $policy->auditSnapshot());

            return $policy;
        });
    }

    public function update(CancellationPolicy $policy, CancellationPolicyData $data, ?Admin $actor): CancellationPolicy
    {
        $this->assertValid($data, false);

        return $this->db->transaction(function () use ($policy, $data, $actor): CancellationPolicy {
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

    public function setActive(CancellationPolicy $policy, bool $active, ?string $reason, ?Admin $actor): CancellationPolicy
    {
        return $this->db->transaction(function () use ($policy, $active, $reason, $actor): CancellationPolicy {
            $locked = $this->lock($policy);

            if ($locked->active === $active) {
                return $locked;
            }

            if ($active && $this->policies->overlapping($locked->service_category_id, $locked->service_zone_id, $locked->effective_from, $locked->effective_to, $locked->id) !== []) {
                throw BusinessRuleViolation::make('cancellation_policy_overlap', 'effective_from');
            }

            $this->policies->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);

            return $locked;
        });
    }

    public function delete(CancellationPolicy $policy, string $reason, ?Admin $actor): void
    {
        $this->db->transaction(function () use ($policy, $reason, $actor): void {
            $locked = $this->lock($policy);

            if ($this->policies->bookingCount($locked) > 0) {
                throw BusinessRuleViolation::make('cancellation_policy_in_use');
            }

            $before = $locked->auditSnapshot();
            $this->policies->delete($locked);
            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    private function assertValid(CancellationPolicyData $data, bool $creating): void
    {
        if ($creating && $data->serviceCategoryId !== null && $this->categories->find($data->serviceCategoryId) === null) {
            throw BusinessRuleViolation::make('cancellation_policy_category_missing', 'service_category_id');
        }

        if ($creating && $data->serviceZoneId !== null && $this->zones->find($data->serviceZoneId) === null) {
            throw BusinessRuleViolation::make('cancellation_policy_zone_missing', 'service_zone_id');
        }

        if ($data->effectiveTo !== null && $data->effectiveTo->lessThanOrEqualTo($data->effectiveFrom)) {
            throw BusinessRuleViolation::make('cancellation_policy_window_invalid', 'effective_to');
        }

        if ($data->freeGraceSeconds > self::MAX_SECONDS || $data->freeWaitSeconds > self::MAX_SECONDS) {
            throw BusinessRuleViolation::make('cancellation_policy_window_invalid', 'free_grace_seconds');
        }

        $this->assertReasons($data->customerReasons, ActorType::Customer, 'customer_reasons');
        $this->assertReasons($data->providerReasons, ActorType::Provider, 'provider_reasons');

        $seen = [];

        foreach ($data->rules as $index => $rule) {
            $key = $rule['actor'].'|'.$rule['stage'];
            $stage = CancellationStage::tryFrom($rule['stage']);
            $type = CancellationFeeType::tryFrom($rule['fee_type']);
            $valid = in_array($rule['actor'], [ActorType::Customer->value, ActorType::Provider->value], true)
                && $stage !== null && in_array($stage, CancellationStage::chargeable(), true)
                && $type !== null
                && CancellationFeeRecipient::tryFrom($rule['recipient']) !== null
                && ! isset($seen[$key])
                && $rule['value'] >= 0
                && ($type !== CancellationFeeType::Fixed || ($rule['value'] > 0 && $rule['value'] <= self::MAX_FEE_MINOR))
                && ($type !== CancellationFeeType::Percent || ($rule['value'] > 0 && $rule['value'] <= 10_000))
                && ($rule['cap_minor'] === null || ($rule['cap_minor'] > 0 && $rule['cap_minor'] <= self::MAX_FEE_MINOR));

            if (! $valid) {
                throw BusinessRuleViolation::make('cancellation_policy_rule_invalid', "rules.{$index}.fee_type");
            }

            $seen[$key] = true;
        }
    }

    /**
     * @param list<string> $reasons
     */
    private function assertReasons(array $reasons, ActorType $actor, string $field): void
    {
        if ($reasons === [] || array_diff($reasons, CancellationReason::valuesForActor($actor)) !== []) {
            throw BusinessRuleViolation::make('cancellation_policy_reasons_invalid', $field);
        }
    }

    private function assertNoOverlap(?int $categoryId, ?int $zoneId, CancellationPolicyData $data, ?int $exceptId): void
    {
        $overlapping = $this->policies->overlapping($categoryId, $zoneId, $data->effectiveFrom, $data->effectiveTo, $exceptId);

        if ($overlapping !== []) {
            throw BusinessRuleViolation::make('cancellation_policy_overlap', 'effective_from');
        }
    }

    private function lock(CancellationPolicy $policy): CancellationPolicy
    {
        $locked = $this->policies->lockById($policy->id);

        return $locked instanceof CancellationPolicy ? $locked : throw (new ModelNotFoundException)->setModel(CancellationPolicy::class);
    }
}
