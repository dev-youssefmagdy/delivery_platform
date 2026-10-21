<?php

namespace App\DTOs\Finance;

use App\Enums\Common\ActorType;
use App\Enums\Finance\JournalEntryType;
use Carbon\CarbonImmutable;

/**
 * Everything LedgerService::post() needs. The idempotency key identifies the business event ("booking-settlement:<uuid>"):
 * posting it twice returns the first entry. `enforceNegativePolicy` is false only for reversals, which must be able to
 * correct an error even where the original credit has already been spent. `nonNegativeAccountIds` lists accounts that
 * must end at zero or more in this entry although their own policy allows negatives (a provider wallet whose balance is
 * reserved for a payout: the reservation can never exceed what is there).
 */
final readonly class JournalEntryData
{
    /**
     * @param list<JournalLineData> $lines
     * @param array<string, mixed> $meta safe context shown on the entry page; never PII
     */
    public function __construct(
        public JournalEntryType $type,
        public string $idempotencyKey,
        public string $descriptionKey,
        public array $lines,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public array $meta = [],
        public ?ActorType $actorType = null,
        public ?int $actorId = null,
        public ?CarbonImmutable $postedAt = null,
        public bool $enforceNegativePolicy = true,
        public ?int $reversalOfId = null,
        /** @var list<int> accounts that must not end below zero in this entry whatever their own policy (a payout reservation) */
        public array $nonNegativeAccountIds = [],
    ) {}

    /**
     * Hash of the lines (account, side, amount, currency), order independent.
     */
    public function fingerprint(): string
    {
        $parts = array_map(
            static fn (JournalLineData $line): string => $line->accountId.':'.$line->side->value.':'.$line->amount->amount.':'.$line->amount->currency->code,
            $this->lines,
        );
        sort($parts);

        return hash('sha256', $this->type->value.'|'.implode('|', $parts));
    }
}
