<?php

namespace App\DTOs\Finance;

/**
 * Cash jobs of a provider in a period, from the stored price breakdowns: the cash the provider collected from customers and
 * what that leaves owed to the platform (customer payable minus what the provider is entitled to; negative balances of single
 * jobs net against each other). Never below zero.
 */
final readonly class CashFigures
{
    public function __construct(
        public int $jobs,
        public int $cashCollectedMinor,
        public int $commissionDueMinor,
    ) {}
}
