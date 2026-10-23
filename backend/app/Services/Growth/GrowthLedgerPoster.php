<?php

namespace App\Services\Growth;

use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\Enums\Common\ActorType;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Services\Finance\LedgerAccountResolver;
use App\Services\Finance\LedgerService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;

/**
 * Every reward, bonus and expiry of the growth features is one balanced ledger entry between the platform `promo_expense` account
 * and the recipient's wallet (SRS 7.6, 11.3): a grant debits promo expense and credits the wallet, an expiry does the opposite.
 * The idempotency key identifies the business event, so a retry never pays twice.
 */
final class GrowthLedgerPoster
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly LedgerAccountResolver $accounts,
        private readonly CurrencyRegistry $currencies,
    ) {}

    /**
     * @param array<string, mixed> $meta safe context only
     */
    public function grant(LedgerAccount $wallet, int $amountMinor, JournalEntryType $type, string $key, string $referenceType, int $referenceId, array $meta = []): JournalEntry
    {
        $amount = Money::of($amountMinor, $this->currencies->get($wallet->currency));
        $promo = $this->accounts->platform(LedgerAccountType::PromoExpense, $wallet->currency);

        return $this->ledger->post(new JournalEntryData(
            type: $type,
            idempotencyKey: $key,
            descriptionKey: 'ledger.entry.'.$type->value,
            lines: [JournalLineData::debit($promo, $amount), JournalLineData::credit($wallet, $amount)],
            referenceType: $referenceType,
            referenceId: $referenceId,
            meta: $meta,
            actorType: ActorType::System,
        ))->entry;
    }

    /**
     * Takes expired, unspent credit back out of a customer wallet (never more than the caller verified to be there).
     *
     * @param array<string, mixed> $meta
     */
    public function takeBack(LedgerAccount $wallet, int $amountMinor, string $key, string $referenceType, int $referenceId, array $meta = []): JournalEntry
    {
        $amount = Money::of($amountMinor, $this->currencies->get($wallet->currency));
        $promo = $this->accounts->platform(LedgerAccountType::PromoExpense, $wallet->currency);

        return $this->ledger->post(new JournalEntryData(
            type: JournalEntryType::CreditExpiry,
            idempotencyKey: $key,
            descriptionKey: 'ledger.entry.credit_expiry',
            lines: [JournalLineData::debit($wallet, $amount), JournalLineData::credit($promo, $amount)],
            referenceType: $referenceType,
            referenceId: $referenceId,
            meta: $meta,
            actorType: ActorType::System,
        ))->entry;
    }
}
