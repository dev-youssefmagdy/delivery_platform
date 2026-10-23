<?php

namespace App\Services\Notification\Channels;

use App\Enums\Notification\NotificationChannel;
use App\Enums\System\HealthArea;
use App\Integrations\Push\Contracts\PushNotifier;
use App\Integrations\Push\Data\PushMessage;
use App\Integrations\Push\Exceptions\PushDeliveryException;
use App\Models\Admin;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use App\Services\Notification\DeliveryOutcome;
use App\Services\System\IntegrationStatusStore;
use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Push to every device of the person that holds a token in the notification's app. One accepted device is a success; an
 * invalid token is forgotten; a temporary provider failure with no success is retried. The lock-screen text is the
 * delivery's own (minimal) text and the payload carries only ids for the deep link (SRS §21).
 */
final class PushChannelSender implements ChannelSender
{
    public function __construct(
        private readonly Container $container,
        private readonly UserDeviceRepositoryInterface $devices,
    ) {}

    public function channel(): NotificationChannel
    {
        return NotificationChannel::Push;
    }

    public function send(NotificationDelivery $delivery, User|Admin $recipient): DeliveryOutcome
    {
        if (! $recipient instanceof User) {
            return DeliveryOutcome::skipped('push_not_for_admins');
        }

        if (config('integrations.push.driver') === null) {
            return DeliveryOutcome::skipped('push_not_configured');
        }

        $notification = $delivery->notification;
        $tokens = $this->devices->withPushToken($recipient, $notification->app);

        if ($tokens->isEmpty()) {
            return DeliveryOutcome::skipped('no_device');
        }

        try {
            $notifier = $this->container->make(PushNotifier::class);
        } catch (Throwable) {
            return DeliveryOutcome::skipped('push_not_configured');
        }

        $data = array_map(strval(...), array_filter([
            'notification_id' => $notification->uuid,
            'event' => $notification->event_key,
            'category' => $notification->category->value,
            'link_type' => $notification->link_type,
            'link_id' => $notification->link_id,
        ] + (array) ($notification->data ?? []), static fn (mixed $value): bool => is_scalar($value) && $value !== ''));

        $accepted = null;
        $temporary = null;
        $permanent = null;
        $invalid = 0;

        foreach ($tokens as $device) {
            try {
                $messageId = $notifier->send(new PushMessage(
                    token: (string) $device->push_token,
                    title: (string) $delivery->title,
                    body: $delivery->body,
                    data: $data,
                    collapseKey: isset($notification->data['collapse_key']) ? substr(sha1((string) $notification->data['collapse_key']), 0, 16) : ($notification->dedupe_key === null ? null : substr(sha1($notification->dedupe_key), 0, 16)),
                ));
                $accepted ??= $messageId;
            } catch (PushDeliveryException $e) {
                if (! $e->invalidToken()) {
                    IntegrationStatusStore::failed(HealthArea::Push, $e);
                }

                if ($e->invalidToken()) {
                    $invalid++;
                    $this->devices->forgetPushTokenByHash((string) ($device->push_token_hash ?? hash('sha256', (string) $device->push_token)));
                } elseif ($e->permanent()) {
                    $permanent = $e;
                } else {
                    $temporary = $e;
                }
            }
        }

        if ($accepted !== null) {
            IntegrationStatusStore::succeeded(HealthArea::Push);

            return DeliveryOutcome::sent($notifier->name(), $accepted);
        }

        if ($temporary !== null) {
            return DeliveryOutcome::retry($temporary->errorCode(), $temporary->getMessage(), $notifier->name());
        }

        if ($permanent !== null) {
            return DeliveryOutcome::failed($permanent->errorCode(), $permanent->getMessage(), $notifier->name());
        }

        return DeliveryOutcome::skipped($invalid > 0 ? 'invalid_token' : 'no_device', $notifier->name());
    }
}
