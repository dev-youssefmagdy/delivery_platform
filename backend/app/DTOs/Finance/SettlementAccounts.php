<?php

namespace App\DTOs\Finance;

/**
 * The account ids a settlement posts to. Resolved by BookingSettlementService; the posting builder only needs ids.
 */
final readonly class SettlementAccounts
{
    public function __construct(
        public int $providerWallet,
        public int $platformRevenue,
        public int $promoExpense,
        public int $partnerReceivable,
        public int $taxesPayable,
        public int $gatewayClearing,
    ) {}
}
