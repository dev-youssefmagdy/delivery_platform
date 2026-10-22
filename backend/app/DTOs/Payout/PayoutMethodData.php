<?php

namespace App\DTOs\Payout;

use App\Enums\Payout\PayoutMethodType;

/**
 * A payout method as the provider submits it. `details` holds the type's fields (holder, bank, account / wallet number).
 */
final readonly class PayoutMethodData
{
    /**
     * @param array<string, string> $details
     */
    public function __construct(
        public PayoutMethodType $type,
        public string $label,
        public array $details,
        public bool $makeDefault = false,
    ) {}
}
