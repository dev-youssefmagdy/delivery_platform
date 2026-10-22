<?php

namespace App\Integrations\Push\Contracts;

use App\Integrations\Push\Data\PushMessage;
use App\Integrations\Push\Exceptions\PushDeliveryException;

/**
 * Sends one push message to one device. The only way the platform reaches FCM / APNs (ADR-12).
 */
interface PushNotifier
{
    /**
     * @return string the provider's message id
     *
     * @throws PushDeliveryException when the provider did not accept the message; `permanent()` tells whether a retry can help
     */
    public function send(PushMessage $message): string;

    /**
     * Short driver name stored on the delivery (`fcm`, `log`).
     */
    public function name(): string;
}
