<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CancellationFeeRecipient;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;
use Carbon\CarbonImmutable;

/**
 * The outcome of applying a cancellation policy to a booking at a moment: what the canceller would owe, why, and from
 * which rule. It is what the preview shows and what the commit stores on the booking (`cancellation_decision`); fees are
 * only computed here, the ledger posts them from P3.5. Money is integer minor units.
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
    ) {}

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
            'stage' => $this->stage->value,
            'fee' => ['amount_minor' => $this->feeMinor, 'currency' => $this->currency],
            'free' => $this->isFree(),
            'basis' => $this->basis,
            'recipient' => $this->recipient?->value,
            'policy_id' => $this->policyId,
            'rule' => $this->ruleType === null ? null : ['type' => $this->ruleType, 'value' => $this->ruleValue],
            'grace_ends_at' => $this->graceEndsAt?->toIso8601ZuluString(),
            'decided_at' => $this->decidedAt->toIso8601ZuluString(),
        ];
    }
}
