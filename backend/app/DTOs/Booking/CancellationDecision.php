<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;
use Carbon\CarbonImmutable;

/**
 * The outcome of applying a cancellation policy to a booking at a moment: what the canceller would owe, why, and from
 * which rule. It is what the preview shows and what the commit stores on the booking (`cancellation_decision`); fees are
 * computed here and posted to the ledger by CancellationFeePostingService (P3.5). Money is integer minor units.
 */
final readonly class CancellationDecision
{
    public const BASIS_NO_PROVIDER = 'no_provider';

    public const BASIS_FREE_GRACE = 'free_grace';

    public const BASIS_FREE_WAIT = 'free_wait';

    public const BASIS_PROVIDER_FAULT = 'provider_fault';

    public const BASIS_JUSTIFIED = 'justified_reason';

    public const BASIS_NO_RULE = 'no_rule';

    public const BASIS_RULE = 'rule';

    public const BASIS_ARRIVAL_UNVERIFIED = 'arrival_unverified';

    public const BASIS_WAIVED_BY_OPERATIONS = 'waived_by_operations';

    public const BASIS_OPERATIONS_POLICY = 'operations_applied_policy';

    /** The customer did not show up after the free wait and the provider's evidence holds (fee from the no_show rule). */
    public const BASIS_NO_SHOW = 'no_show';

    /** A no-show was reported but the policy needs GPS + time evidence that is missing: nothing is charged. */
    public const BASIS_NO_SHOW_NO_EVIDENCE = 'no_show_no_evidence';

    /** The customer reported a provider that never arrived: the customer pays nothing, the provider's penalty applies. */
    public const BASIS_PROVIDER_NO_SHOW = 'provider_no_show';

    public function __construct(
        public ActorType $actor,
        public CancellationStage $stage,
        public int $feeMinor,
        public string $currency,
        public string $basis,
        public ?CancellationFeeRecipient $recipient,
        public ?int $policyId,
        public ?CarbonImmutable $graceEndsAt,
        public CarbonImmutable $decidedAt,
        public ?string $ruleType = null,
        public ?int $ruleValue = null,
        /** Who owes `feeMinor` when it is not the canceller (a reported customer no-show, an operator applying the policy). */
        public ?ActorType $payer = null,
        /** Share of the fee, in basis points, that goes to the provider when the recipient is the provider (rest: platform). */
        public ?int $providerShareBp = null,
        /** @var array{amount_minor: int, recipient: string, rule: array{type: string, value: int}|null}|null a second fee: the provider's no-show penalty */
        public ?array $penalty = null,
        /** @var array<string, mixed>|null the GPS and time evidence a no-show decision rests on */
        public ?array $evidence = null,
    ) {}

    /**
     * Who owes the fee: the payer when set, otherwise the actor that cancelled.
     */
    public function payer(): ActorType
    {
        return $this->payer ?? $this->actor;
    }

    public function penaltyMinor(): int
    {
        return (int) ($this->penalty['amount_minor'] ?? 0);
    }

    public function isFree(): bool
    {
        return $this->feeMinor === 0;
    }

    /**
     * The shape the API returns and the booking stores.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'actor' => $this->actor->value,
            'payer' => $this->payer()->value,
            'stage' => $this->stage->value,
            'fee' => ['amount_minor' => $this->feeMinor, 'currency' => $this->currency],
            'free' => $this->isFree(),
            'basis' => $this->basis,
            'recipient' => $this->recipient?->value,
            'policy_id' => $this->policyId,
            'rule' => $this->ruleType === null ? null : ['type' => $this->ruleType, 'value' => $this->ruleValue],
            'provider_share_bp' => $this->providerShareBp,
            'penalty' => $this->penalty,
            'evidence' => $this->evidence,
            'grace_ends_at' => $this->graceEndsAt?->toIso8601ZuluString(),
            'decided_at' => $this->decidedAt->toIso8601ZuluString(),
        ];
    }
}
