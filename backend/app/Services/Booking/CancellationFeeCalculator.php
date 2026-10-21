<?php

namespace App\Services\Booking;

use App\DTOs\Booking\CancellationDecision;
use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Booking\CancellationFeeType;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Applies the cancellation policy a booking was created under (its own snapshot, Plan I4) to a moment and an actor
 * (SRS §8.4). Pure: it reads the booking and writes nothing, so the preview and the commit run the very same code.
 *
 * - Before a provider is selected nothing is owed.
 * - The customer owes nothing inside the free grace period after selection, while the provider's free waiting time has
 *   not ended, or when the provider asked for the cancellation.
 * - An arrival without verified GPS evidence is treated as "on the way": a single missing or distant GPS signal never
 *   triggers the arrival fee.
 * - A provider who cancels for a justified reason (no-show, unreachable, unsafe, wrong details, emergency) owes nothing.
 * Fees are integer minor units: a fixed amount, or basis points of the agreed fare rounded half-up, capped.
 */
final class CancellationFeeCalculator
{
    public function __construct(private readonly CurrencyRegistry $currencies) {}

    /**
     * @throws BusinessRuleViolation `booking_not_cancellable` when the booking is not in a cancellable stage
     */
    public function decide(Booking $booking, ActorType $actor, CarbonImmutable $now, ?CancellationReason $reason = null): CancellationDecision
    {
        $stage = CancellationStage::of($booking->status) ?? throw BusinessRuleViolation::make('booking_not_cancellable');
        $policy = (array) ($booking->policy_snapshot['cancellation'] ?? CancellationPolicyResolver::defaults());
        $policyId = isset($policy['id']) ? (int) $policy['id'] : null;
        $currency = $booking->currency;
        $graceEndsAt = $booking->provider_selected_at?->toImmutable()->addSeconds((int) ($policy['free_grace_seconds'] ?? 0));

        $free = static fn (string $basis): CancellationDecision => new CancellationDecision($actor, $stage, 0, $currency, $basis, null, $policyId, $graceEndsAt, $now);

        if ($stage === CancellationStage::Searching) {
            return $free(CancellationDecision::BASIS_NO_PROVIDER);
        }

        if ($actor === ActorType::Provider) {
            if ($reason !== null && in_array($reason, self::justifiedForProvider(), true)) {
                return $free(CancellationDecision::BASIS_JUSTIFIED);
            }

            return $this->fromRule($booking, $policy, $actor, $stage, $stage, $now, $graceEndsAt, $policyId, CancellationDecision::BASIS_RULE);
        }

        if ($reason === CancellationReason::DriverAskedToCancel) {
            return $free(CancellationDecision::BASIS_PROVIDER_FAULT);
        }

        if ($graceEndsAt !== null && $graceEndsAt->greaterThanOrEqualTo($now)) {
            return $free(CancellationDecision::BASIS_FREE_GRACE);
        }

        $ruleStage = $stage;
        $basis = CancellationDecision::BASIS_RULE;

        if ($stage === CancellationStage::Arrived) {
            if ($booking->free_wait_ends_at !== null && $booking->free_wait_ends_at->greaterThan($now)) {
                return $free(CancellationDecision::BASIS_FREE_WAIT);
            }

            if (! (bool) ($booking->arrival_evidence['verified'] ?? false)) {
                $ruleStage = CancellationStage::HeadingToPickup;
                $basis = CancellationDecision::BASIS_ARRIVAL_UNVERIFIED;
            }
        }

        return $this->fromRule($booking, $policy, $actor, $stage, $ruleStage, $now, $graceEndsAt, $policyId, $basis);
    }

    /**
     * Reasons that excuse a provider from a cancellation fee.
     *
     * @return list<CancellationReason>
     */
    public static function justifiedForProvider(): array
    {
        return [
            CancellationReason::CustomerNoShow, CancellationReason::CustomerUnreachable, CancellationReason::UnsafePickup,
            CancellationReason::WrongRequestDetails, CancellationReason::Emergency,
        ];
    }

    /**
     * @param array<string, mixed> $policy
     */
    private function fromRule(Booking $booking, array $policy, ActorType $actor, CancellationStage $stage, CancellationStage $ruleStage, CarbonImmutable $now, ?CarbonImmutable $graceEndsAt, ?int $policyId, string $basis): CancellationDecision
    {
        $rule = null;

        foreach ((array) ($policy['rules'] ?? []) as $candidate) {
            if (($candidate['actor'] ?? null) === $actor->value && ($candidate['stage'] ?? null) === $ruleStage->value) {
                $rule = (array) $candidate;

                break;
            }
        }

        $type = $rule === null ? CancellationFeeType::None : (CancellationFeeType::tryFrom((string) ($rule['fee_type'] ?? '')) ?? CancellationFeeType::None);
        $value = (int) ($rule['value'] ?? 0);
        $fare = (int) ($booking->agreed_fare_minor ?? 0);

        $fee = match ($type) {
            CancellationFeeType::None => 0,
            CancellationFeeType::Fixed => $value,
            CancellationFeeType::Percent => Money::of($fare, $this->currencies->get($booking->currency))->multiplyBasisPoints($value)->amount,
        };

        $cap = isset($rule['cap_minor']) && $rule['cap_minor'] !== null ? (int) $rule['cap_minor'] : null;

        if ($cap !== null) {
            $fee = min($fee, $cap);
        }

        $fee = max(0, $fee);

        return new CancellationDecision(
            $actor,
            $stage,
            $fee,
            $booking->currency,
            $rule === null ? CancellationDecision::BASIS_NO_RULE : $basis,
            $fee > 0 ? (CancellationFeeRecipient::tryFrom((string) ($rule['recipient'] ?? '')) ?? CancellationFeeRecipient::Platform) : null,
            $policyId,
            $graceEndsAt,
            $now,
            $rule === null ? null : $type->value,
            $rule === null ? null : $value,
        );
    }
}
