<?php

namespace App\DTOs\Finance;

use Carbon\CarbonImmutable;

/**
 * Opens a cash reconciliation: the provider and the period whose cash jobs are summed (inclusive, market dates).
 */
final readonly class CashReconciliationOpenData
{
    public function __construct(
        public int $providerId,
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public ?string $notes,
        public string $idempotencyKey,
    ) {}
}
