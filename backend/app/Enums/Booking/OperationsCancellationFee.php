<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The explicit fee decision of a cancellation by operations (SRS §16.6): follow the booking's policy snapshot or waive
 * the fee. Operations never invent an amount.
 */
enum OperationsCancellationFee: string implements HasLabel
{
    use EnumHelpers;

    case Waive = 'waive';
    case ApplyPolicy = 'apply_policy';

    public function label(): string
    {
        return __("enums.operations_cancellation_fee.{$this->value}");
    }
}
