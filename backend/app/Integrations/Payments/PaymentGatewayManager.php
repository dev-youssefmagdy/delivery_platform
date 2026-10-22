<?php

namespace App\Integrations\Payments;

use App\Enums\Payments\PaymentGatewayName;
use App\Exceptions\ServiceUnavailableException;
use App\Integrations\Payments\Contracts\PaymentGateway;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the configured payment gateway. One driver is active per deployment (PAYMENT_DRIVER); a webhook for any other
 * gateway name is refused, so a callback cannot choose which verification code runs. With no driver configured, online
 * payments are unavailable (the feature stays hidden, never faked).
 */
final class PaymentGatewayManager
{
    public function __construct(private readonly Container $container) {}

    public function configuredName(): ?PaymentGatewayName
    {
        $driver = config('integrations.payments.driver');

        return is_string($driver) ? PaymentGatewayName::tryFrom($driver) : null;
    }

    public function isConfigured(): bool
    {
        return $this->configuredName() instanceof PaymentGatewayName;
    }

    /**
     * @throws ServiceUnavailableException `payments_not_configured`
     */
    public function default(): PaymentGateway
    {
        if (! $this->isConfigured()) {
            throw ServiceUnavailableException::make('payments_not_configured');
        }

        return $this->container->make(PaymentGateway::class);
    }

    /**
     * The gateway for a callback or a stored payment; null when it is not the configured one.
     */
    public function forName(PaymentGatewayName $name): ?PaymentGateway
    {
        return $this->configuredName() === $name ? $this->container->make(PaymentGateway::class) : null;
    }
}
