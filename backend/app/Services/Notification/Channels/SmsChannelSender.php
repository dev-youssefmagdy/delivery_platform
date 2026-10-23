<?php

namespace App\Services\Notification\Channels;

use App\Enums\Notification\NotificationChannel;
use App\Enums\System\HealthArea;
use App\Integrations\Sms\Contracts\SmsGateway;
use App\Integrations\Sms\Exceptions\SmsDeliveryException;
use App\Models\Admin;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Services\Notification\DeliveryOutcome;
use App\Services\System\IntegrationStatusStore;
use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Text message to the person's verified phone through the shared `SmsGateway` (the same adapter OTP uses). The message
 * body never contains an OTP, and neither the number nor the body is logged here.
 */
final class SmsChannelSender implements ChannelSender
{
    public function __construct(private readonly Container $container) {}

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Sms;
    }

    public function send(NotificationDelivery $delivery, User|Admin $recipient): DeliveryOutcome
    {
        $phone = $recipient instanceof User ? $recipient->phone_e164 : $recipient->phone;

        if ($phone === null || $phone === '') {
            return DeliveryOutcome::skipped('no_phone');
        }

        if (config('integrations.sms.driver') === null) {
            return DeliveryOutcome::skipped('sms_not_configured');
        }

        try {
            $gateway = $this->container->make(SmsGateway::class);
            $gateway->send($phone, $delivery->body);
        } catch (SmsDeliveryException $e) {
            IntegrationStatusStore::failed(HealthArea::Sms, $e);

            return DeliveryOutcome::retry('sms_failed', 'The SMS provider did not accept the message.');
        } catch (Throwable) {
            return DeliveryOutcome::skipped('sms_not_configured');
        }

        IntegrationStatusStore::succeeded(HealthArea::Sms);

        return DeliveryOutcome::sent($gateway->name());
    }
}
