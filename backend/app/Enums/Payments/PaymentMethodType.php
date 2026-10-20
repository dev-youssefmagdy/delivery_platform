<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Payment methods (SRS §12.1). Categories restrict the subset they accept; gateways arrive in P3.2.
 */
enum PaymentMethodType: string implements HasLabel
{
    use EnumHelpers;

    case Cash = 'cash';
    case Card = 'card';
    case MobileWallet = 'mobile_wallet';
    case PlatformWallet = 'platform_wallet';

    public function label(): string
    {
        return __("enums.payment_method_type.{$this->value}");
    }
}
