<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a payment pays for: the trip itself or a tip left afterwards (a separate charge, commission-free by default).
 */
enum PaymentKind: string implements HasLabel
{
    use EnumHelpers;

    case Trip = 'trip';
    case Tip = 'tip';

    public function label(): string
    {
        return __("enums.payment_kind.{$this->value}");
    }
}
