<?php

namespace App\DTOs\Payout;

/**
 * What a provider can withdraw. `walletMinor` is the wallet balance AFTER the reservations of open payouts (the reservation
 * is a ledger entry, so it is already out of the wallet); `reservedMinor` is the sum of those open payouts, shown for transparency.
 */
final readonly class ProviderBalance
{
    public function __construct(
        public string $currency,
        public int $walletMinor,
        public int $reservedMinor,
        public int $availableMinor,
        public int $minMinor,
        public int $maxMinor,
    ) {}
}
