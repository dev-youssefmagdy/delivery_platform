<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Who receives a cancellation fee once the ledger posts it (P3.5): the provider (compensation) or the platform.
 */
enum CancellationFeeRecipient: string implements HasLabel
{
    use EnumHelpers;

    case Provider = 'provider';
    case Platform = 'platform';

    public function label(): string
    {
        return __("enums.cancellation_fee_recipient.{$this->value}");
    }
}
