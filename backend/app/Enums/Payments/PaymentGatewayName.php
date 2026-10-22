<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The payment gateway drivers the platform can run (ADR-12). `sandbox` exists only in local/testing (boot guard);
 * `paymob` is the real Egyptian-market driver. `cash` is not a gateway: payments in cash carry no gateway name.
 */
enum PaymentGatewayName: string implements HasLabel
{
    use EnumHelpers;

    case Sandbox = 'sandbox';
    case Paymob = 'paymob';

    public function label(): string
    {
        return __("enums.payment_gateway.{$this->value}");
    }
}
