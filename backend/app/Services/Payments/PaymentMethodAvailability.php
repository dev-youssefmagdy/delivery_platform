<?php

namespace App\Services\Payments;

use App\Enums\Payments\PaymentMethodType;
use App\Integrations\Payments\PaymentGatewayManager;

/**
 * Which payment methods customers can pick today: the deployment's list (cash by default) plus card and mobile wallet
 * once a payment gateway is configured. Without a gateway online methods stay hidden: they are never faked.
 */
final class PaymentMethodAvailability
{
    public function __construct(private readonly PaymentGatewayManager $gateways) {}

    /**
     * @return list<string>
     */
    public function enabled(): array
    {
        $enabled = array_values(array_map('strval', (array) config('booking.enabled_payment_methods', [])));

        if ($this->gateways->isConfigured()) {
            $enabled = array_values(array_unique([...$enabled, PaymentMethodType::Card->value, PaymentMethodType::MobileWallet->value]));
        } else {
            $enabled = array_values(array_diff($enabled, [PaymentMethodType::Card->value, PaymentMethodType::MobileWallet->value]));
        }

        return $enabled;
    }
}
