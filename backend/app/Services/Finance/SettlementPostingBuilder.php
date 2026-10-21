<?php

namespace App\Services\Finance;

use App\DTOs\Finance\JournalLineData;
use App\DTOs\Finance\PriceBreakdown;
use App\DTOs\Finance\SettlementAccounts;
use App\Support\Money\Money;

/**
 * Turns a price breakdown into journal lines (Plan §7.4). Pure: no database, no clock. Zero amounts are left out; an
 * empty list means there is nothing to post. Every result balances by construction and LedgerService checks it again.
 *
 * Cash: the driver holds the customer payable, so the provider wallet moves by what the provider is entitled to
 * minus the cash already in their hands (negative = commission owed to the platform).
 * Gateway: the platform holds the money in gateway clearing and credits the provider's entitlement directly.
 */
final class SettlementPostingBuilder
{
    /**
     * @return list<JournalLineData>
     */
    public function cash(PriceBreakdown $breakdown, SettlementAccounts $accounts): array
    {
        $walletDelta = $breakdown->providerEarning->subtract($breakdown->customerPayable);
        $lines = [];

        $this->credit($lines, $accounts->platformRevenue, $breakdown->platformRevenue);
        $this->credit($lines, $accounts->taxesPayable, $breakdown->tax);
        $this->debit($lines, $accounts->promoExpense, $breakdown->discountPlatform);
        $this->debit($lines, $accounts->partnerReceivable, $breakdown->discountPartner);

        if ($walletDelta->isPositive()) {
            $this->credit($lines, $accounts->providerWallet, $walletDelta);
        } else {
            $this->debit($lines, $accounts->providerWallet, $walletDelta->negate());
        }

        return $lines;
    }

    /**
     * The trip part of a card or wallet payment. The tip is a separate entry ({@see self::gatewayTip()}).
     *
     * @return list<JournalLineData>
     */
    public function gateway(PriceBreakdown $breakdown, SettlementAccounts $accounts): array
    {
        $collected = $breakdown->customerPayable->subtract($breakdown->tip);
        $providerCredit = $breakdown->providerGross->subtract($breakdown->commission, $breakdown->discountProvider);
        $lines = [];

        $this->debit($lines, $accounts->gatewayClearing, $collected);
        $this->debit($lines, $accounts->promoExpense, $breakdown->discountPlatform);
        $this->debit($lines, $accounts->partnerReceivable, $breakdown->discountPartner);
        $this->credit($lines, $accounts->platformRevenue, $breakdown->commission->add($breakdown->customerFee));
        $this->credit($lines, $accounts->taxesPayable, $breakdown->tax);

        if ($providerCredit->isNegative()) {
            $this->debit($lines, $accounts->providerWallet, $providerCredit->negate());
        } else {
            $this->credit($lines, $accounts->providerWallet, $providerCredit);
        }

        return $lines;
    }

    /**
     * A tip paid by card: commission-free unless the rule version says otherwise (ADR-18).
     *
     * @return list<JournalLineData>
     */
    public function gatewayTip(PriceBreakdown $breakdown, SettlementAccounts $accounts): array
    {
        $lines = [];

        $this->debit($lines, $accounts->gatewayClearing, $breakdown->tip);
        $this->credit($lines, $accounts->providerWallet, $breakdown->tip->subtract($breakdown->tipCommission));
        $this->credit($lines, $accounts->platformRevenue, $breakdown->tipCommission);

        return $lines;
    }

    /**
     * @param list<JournalLineData> $lines
     */
    private function debit(array &$lines, int $account, Money $amount): void
    {
        if ($amount->isPositive()) {
            $lines[] = JournalLineData::debit($account, $amount);
        }
    }

    /**
     * @param list<JournalLineData> $lines
     */
    private function credit(array &$lines, int $account, Money $amount): void
    {
        if ($amount->isPositive()) {
            $lines[] = JournalLineData::credit($account, $amount);
        }
    }
}
