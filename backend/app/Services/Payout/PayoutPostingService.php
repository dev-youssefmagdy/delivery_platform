<?php

namespace App\Services\Payout;

use App\DTOs\Finance\JournalEntryData;
use App\DTOs\Finance\JournalLineData;
use App\Enums\Common\ActorType;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Models\Admin;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use App\Services\Finance\LedgerAccountResolver;
use App\Services\Finance\LedgerService;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use LogicException;

/**
 * The three ledger movements of a payout (Plan §7.4), all through LedgerService and all idempotent by key:
 *
 * - reserve:  Dr provider wallet / Cr payout clearing (amount). The wallet may not go below zero because of it.
 * - release:  the reversal of the reserve entry (rejected, cancelled, failed): the money is back in the wallet.
 * - pay:      Dr payout clearing (amount) / Cr gateway clearing (net, the money that left) and Cr platform revenue (fee).
 *
 * A paid payout that comes back is two reversals (the paid entry, then the reservation).
 */
final class PayoutPostingService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly LedgerAccountResolver $accounts,
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly CurrencyRegistry $currencies,
    ) {}

    public function reserve(PayoutRequest $payout, LedgerAccount $wallet): JournalEntry
    {
        $amount = $this->money($payout->amount_minor, $payout->currency);
        $clearing = $this->accounts->platform(LedgerAccountType::PayoutClearing, $payout->currency);

        return $this->ledger->post(new JournalEntryData(
            type: JournalEntryType::PayoutReserve,
            idempotencyKey: 'payout-reserve:'.$payout->uuid,
            descriptionKey: 'ledger.entry.payout_reserve',
            lines: [JournalLineData::debit($wallet, $amount), JournalLineData::credit($clearing, $amount)],
            referenceType: 'payout_request',
            referenceId: $payout->id,
            meta: ['payout' => $payout->uuid],
            actorType: ActorType::System,
            nonNegativeAccountIds: [$wallet->id],
        ))->entry;
    }

    public function release(PayoutRequest $payout, string $reason, ?Admin $actor): JournalEntry
    {
        return $this->ledger->reverse($this->entry($payout->reserve_entry_id), $reason, $actor)->entry;
    }

    public function pay(PayoutRequest $payout, ?Admin $actor): JournalEntry
    {
        $clearing = $this->accounts->platform(LedgerAccountType::PayoutClearing, $payout->currency);
        $gateway = $this->accounts->platform(LedgerAccountType::GatewayClearing, $payout->currency);
        $revenue = $this->accounts->platform(LedgerAccountType::PlatformRevenue, $payout->currency);
        $lines = [
            JournalLineData::debit($clearing, $this->money($payout->amount_minor, $payout->currency)),
            JournalLineData::credit($gateway, $this->money($payout->net_minor, $payout->currency)),
        ];

        if ($payout->fee_minor > 0) {
            $lines[] = JournalLineData::credit($revenue, $this->money($payout->fee_minor, $payout->currency));
        }

        return $this->ledger->post(new JournalEntryData(
            type: JournalEntryType::PayoutPaid,
            idempotencyKey: 'payout-paid:'.$payout->uuid,
            descriptionKey: 'ledger.entry.payout_paid',
            lines: $lines,
            referenceType: 'payout_request',
            referenceId: $payout->id,
            meta: ['payout' => $payout->uuid, 'transfer_reference' => $payout->transfer_reference, 'fee_minor' => $payout->fee_minor],
            actorType: $actor instanceof Admin ? ActorType::Admin : ActorType::System,
            actorId: $actor?->id,
            enforceNegativePolicy: false,
        ))->entry;
    }

    /**
     * A paid transfer that came back: the paid entry and then the reservation are reversed, so the money is in the wallet again.
     */
    public function reversePaid(PayoutRequest $payout, string $reason, ?Admin $actor): JournalEntry
    {
        $this->ledger->reverse($this->entry($payout->paid_entry_id), $reason, $actor);

        return $this->release($payout, $reason, $actor);
    }

    private function entry(?int $id): JournalEntry
    {
        return ($id === null ? null : $this->entries->find($id)) ?? throw new LogicException('The payout has no ledger entry to reverse.');
    }

    private function money(int $minor, string $currency): Money
    {
        return Money::of($minor, $this->currencies->get($currency));
    }
}
