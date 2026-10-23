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
 * - No-shows (P3.5): a provider who reports `customer_no_show` after the free wait charges the customer the `no_show` rule,
 *   but only on GPS + time evidence when the policy requires it (no evidence, no fee); a customer who reports
 *   `driver_no_show` owes nothing and the provider's own `no_show` rule applies to the provider.
 * - A rule may carry `after_seconds`: the rule with the largest threshold already reached applies (time since selection,
 *   or since arrival for the arrival stage), which gives the "fee grows with time" formulas of SRS §8.4.
 * Fees are integer minor units: a fixed amount, or basis points of the agreed fare rounded half-up, capped.
 */
final class CancellationFeeCalculator
{
    public function __construct(private readonly CurrencyRegistry $currencies) {}

    /**
     * @param array<string, mixed>|null $evidence the evidence the reporter of a no-show sent (see NoShowEvidenceService)
     *
     * @throws BusinessRuleViolation `booking_not_cancellable` when the booking is not in a cancellable stage, `provider_no_show_too_early`
     */
    public function decide(Booking $booking, ActorType $actor, CarbonImmutable $now, ?CancellationReason $reason = null, ?array $evidence = null): CancellationDecision
    {
        $stage = CancellationStage::of($booking->status) ?? throw BusinessRuleViolation::make('booking_not_cancellable');
        $policy = (array) ($booking->policy_snapshot['cancellation'] ?? CancellationPolicyResolver::defaults());

        if ($actor === ActorType::Provider && $reason === CancellationReason::CustomerNoShow) {
            return $this->customerNoShow($booking, $policy, $now, $evidence);
        }

        if ($actor === ActorType::Customer && $reason === CancellationReason::DriverNoShow) {
            return $this->providerNoShow($booking, $policy, $stage, $now);
        }

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

            return $this->fromRule($booking, $policy, $actor, $actor, $stage, $stage, $now, $graceEndsAt, $policyId, CancellationDecision::BASIS_RULE);
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

        return $this->fromRule($booking, $policy, $actor, $actor, $stage, $ruleStage, $now, $graceEndsAt, $policyId, $basis);
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
     * The provider says the customer never came. Needs the arrival and the end of the free wait (the service checks the
     * timing); with `require_no_show_evidence` the arrival must have been GPS-verified and the report must carry a fresh
     * fix near the pickup, otherwise nothing is charged.
     *
     * @param array<string, mixed> $policy
     * @param array<string, mixed>|null $evidence
     */
    private function customerNoShow(Booking $booking, array $policy, CarbonImmutable $now, ?array $evidence): CancellationDecision
    {
        $policyId = isset($policy['id']) ? (int) $policy['id'] : null;
        $graceEndsAt = $booking->free_wait_ends_at?->toImmutable();
        $required = (bool) ($policy['require_no_show_evidence'] ?? true);
        $arrivalVerified = (bool) ($booking->arrival_evidence['verified'] ?? false);
        $reportVerified = (bool) ($evidence['verified'] ?? false);
        $summary = [
            'arrival_verified' => $arrivalVerified,
            'report_verified' => $reportVerified,
            'waited_seconds' => $booking->arrived_at === null ? null : max(0, (int) $booking->arrived_at->diffInSeconds($now, false)),
            'free_wait_ends_at' => $graceEndsAt?->toIso8601ZuluString(),
            'reported_at' => $now->toIso8601ZuluString(),
            'distance_to_pickup_m' => $evidence['distance_to_pickup_m'] ?? null,
        ];

        if ($required && (! $arrivalVerified || ! $reportVerified)) {
            return new CancellationDecision(ActorType::Provider, CancellationStage::NoShow, 0, $booking->currency, CancellationDecision::BASIS_NO_SHOW_NO_EVIDENCE, null, $policyId, $graceEndsAt, $now, payer: ActorType::Customer, evidence: $summary);
        }

        $decision = $this->fromRule($booking, $policy, ActorType::Customer, ActorType::Provider, CancellationStage::NoShow, CancellationStage::NoShow, $now, $graceEndsAt, $policyId, CancellationDecision::BASIS_NO_SHOW);

        return $this->withEvidence($decision, $summary);
    }

    /**
     * The customer says the provider never arrived. Possible once `provider_no_show_after_seconds` passed since the
     * selection and while no verified arrival exists; the customer owes nothing and the provider's `no_show` rule is
     * recorded as a penalty owed to the platform.
     *
     * @param array<string, mixed> $policy
     *
     * @throws BusinessRuleViolation `provider_no_show_too_early`
     */
    private function providerNoShow(Booking $booking, array $policy, CancellationStage $stage, CarbonImmutable $now): CancellationDecision
    {
        $policyId = isset($policy['id']) ? (int) $policy['id'] : null;
        $after = (int) ($policy['provider_no_show_after_seconds'] ?? config('booking.cancellation_defaults.provider_no_show_after_seconds', 600));
        $opensAt = $booking->provider_selected_at?->toImmutable()->addSeconds($after);
        $arrivedVerified = $stage === CancellationStage::Arrived && (bool) ($booking->arrival_evidence['verified'] ?? false);

        if ($stage === CancellationStage::Searching || $arrivedVerified || $opensAt === null || $opensAt->greaterThan($now)) {
            throw BusinessRuleViolation::make('provider_no_show_too_early', 'reason_code', ['opens_at' => $opensAt?->toIso8601ZuluString()]);
        }

        $penalty = $this->fromRule($booking, $policy, ActorType::Provider, ActorType::Customer, CancellationStage::NoShow, CancellationStage::NoShow, $now, $opensAt, $policyId, CancellationDecision::BASIS_RULE);

        return new CancellationDecision(
            ActorType::Customer,
            CancellationStage::NoShow,
            0,
            $booking->currency,
            CancellationDecision::BASIS_PROVIDER_NO_SHOW,
            null,
            $policyId,
            $opensAt,
            $now,
            payer: ActorType::Customer,
            penalty: $penalty->feeMinor > 0 ? [
                'amount_minor' => $penalty->feeMinor,
                'recipient' => CancellationFeeRecipient::Platform->value,
                'rule' => $penalty->ruleType === null ? null : ['type' => $penalty->ruleType, 'value' => (int) $penalty->ruleValue],
            ] : null,
            evidence: [
                'selected_at' => $booking->provider_selected_at->toIso8601ZuluString(),
                'window_opened_at' => $opensAt->toIso8601ZuluString(),
                'arrival_verified' => $arrivedVerified,
                'reported_at' => $now->toIso8601ZuluString(),
            ],
        );
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function withEvidence(CancellationDecision $decision, array $summary): CancellationDecision
    {
        return new CancellationDecision(
            $decision->actor, $decision->stage, $decision->feeMinor, $decision->currency, $decision->basis, $decision->recipient,
            $decision->policyId, $decision->graceEndsAt, $decision->decidedAt, $decision->ruleType, $decision->ruleValue,
            $decision->payer, $decision->providerShareBp, $decision->penalty, $summary,
        );
    }

    /**
     * Picks the rule of `$ruleActor` for `$ruleStage` (the one with the largest `after_seconds` already reached) and
     * prices it. `$canceller` is who ended the booking; `$ruleActor` is who the rule charges.
     *
     * @param array<string, mixed> $policy
     */
    private function fromRule(Booking $booking, array $policy, ActorType $ruleActor, ActorType $canceller, CancellationStage $stage, CancellationStage $ruleStage, CarbonImmutable $now, ?CarbonImmutable $graceEndsAt, ?int $policyId, string $basis): CancellationDecision
    {
        $rule = null;
        $reference = $ruleStage === CancellationStage::Arrived || $ruleStage === CancellationStage::NoShow ? $booking->arrived_at : $booking->provider_selected_at;
        $elapsed = $reference === null ? 0 : max(0, (int) $reference->diffInSeconds($now, false));

        foreach ((array) ($policy['rules'] ?? []) as $candidate) {
            if (($candidate['actor'] ?? null) !== $ruleActor->value || ($candidate['stage'] ?? null) !== $ruleStage->value) {
                continue;
            }

            $after = (int) ($candidate['after_seconds'] ?? 0);

            if ($after <= $elapsed && ($rule === null || $after >= (int) ($rule['after_seconds'] ?? 0))) {
                $rule = (array) $candidate;
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

        $cap = isset($rule['cap_minor']) ? (int) $rule['cap_minor'] : null;

        if ($cap !== null) {
            $fee = min($fee, $cap);
        }

        $fee = max(0, $fee);
        $recipient = $fee > 0 ? (CancellationFeeRecipient::tryFrom((string) ($rule['recipient'] ?? '')) ?? CancellationFeeRecipient::Platform) : null;
        // A provider never receives their own fee, and a fee paid to the provider is split by the rule's share (default: all of it).
        $recipient = $ruleActor === ActorType::Provider && $recipient !== null ? CancellationFeeRecipient::Platform : $recipient;
        $share = $recipient === CancellationFeeRecipient::Provider ? min(10_000, max(0, (int) ($rule['provider_share_bp'] ?? 10_000))) : null;

        return new CancellationDecision(
            $canceller,
            $stage,
            $fee,
            $booking->currency,
            $rule === null ? CancellationDecision::BASIS_NO_RULE : $basis,
            $recipient,
            $policyId,
            $graceEndsAt,
            $now,
            $rule === null ? null : $type->value,
            $rule === null ? null : $value,
            $ruleActor === $canceller ? null : $ruleActor,
            $share,
        );
    }
}
