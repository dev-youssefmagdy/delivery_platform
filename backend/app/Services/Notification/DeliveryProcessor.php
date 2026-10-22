<?php

namespace App\Services\Notification;

use App\Enums\Notification\DeliveryStatus;
use App\Enums\Notification\NotificationChannel;
use App\Jobs\Notification\SendNotificationDelivery;
use App\Models\Admin;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use App\Services\Notification\Channels\ChannelSender;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Performs one attempt of a queued delivery (SRS §14.1 retry policy): claims the row under a lock so two workers never
 * send the same delivery, calls the channel, then records the result. A temporary failure schedules the next attempt
 * with the configured back-off until `max_attempts`; after that, or on a permanent failure, the delivery is `failed`
 * with the reason. Delivery is at-least-once: a worker that dies between the provider's answer and the update can cause
 * one repeat, which the push collapse key hides on the device.
 */
final class DeliveryProcessor
{
    /** @var array<string, ChannelSender> */
    private array $senders = [];

    /**
     * @param iterable<ChannelSender> $senders
     */
    public function __construct(
        iterable $senders,
        private readonly NotificationDeliveryRepositoryInterface $deliveries,
        private readonly DatabaseManager $db,
    ) {
        foreach ($senders as $sender) {
            $this->senders[$sender->channel()->value] = $sender;
        }
    }

    public function process(int $deliveryId): void
    {
        $delivery = $this->claim($deliveryId);

        if ($delivery === null) {
            return;
        }

        $outcome = $this->attempt($delivery);
        $maxAttempts = max(1, (int) config('notifications.max_attempts', 5));

        match ($outcome->kind) {
            'sent' => $this->deliveries->update($delivery, [
                'status' => DeliveryStatus::Sent,
                'provider' => $outcome->provider,
                'provider_message_id' => $outcome->messageId,
                'error_code' => null,
                'error_message' => null,
                'sent_at' => now(),
                'next_attempt_at' => null,
            ]),
            'skipped' => $this->deliveries->update($delivery, [
                'status' => DeliveryStatus::Skipped,
                'provider' => $outcome->provider,
                'error_code' => $outcome->errorCode,
                'next_attempt_at' => null,
            ]),
            'retry' => $delivery->attempts >= $maxAttempts
                ? $this->fail($delivery, $outcome, 'retries_exhausted')
                : $this->scheduleRetry($delivery, $outcome),
            default => $this->fail($delivery, $outcome),
        };
    }

    /**
     * Takes the delivery for this attempt, or null when it is finished, not due yet, or claimed by another worker.
     */
    private function claim(int $deliveryId): ?NotificationDelivery
    {
        return $this->db->transaction(function () use ($deliveryId): ?NotificationDelivery {
            $delivery = $this->deliveries->lockById($deliveryId);

            if (! $delivery instanceof NotificationDelivery || $delivery->status->isFinal()) {
                return null;
            }

            // Another worker is mid-attempt (it set `sending` less than two minutes ago).
            if ($delivery->status === DeliveryStatus::Sending && $delivery->last_attempt_at !== null && $delivery->last_attempt_at->gt(now()->subMinutes(2))) {
                return null;
            }

            if ($delivery->next_attempt_at !== null && $delivery->next_attempt_at->isFuture()) {
                return null;
            }

            $this->deliveries->update($delivery, ['status' => DeliveryStatus::Sending, 'attempts' => $delivery->attempts + 1, 'last_attempt_at' => now()]);

            return $delivery;
        });
    }

    private function attempt(NotificationDelivery $delivery): DeliveryOutcome
    {
        $sender = $this->senders[$delivery->channel->value] ?? null;

        if ($sender === null || $delivery->channel === NotificationChannel::InApp) {
            return DeliveryOutcome::skipped('channel_unsupported');
        }

        $delivery->loadMissing('notification');
        $notification = $delivery->notification;
        $recipient = $notification->recipient;

        if (! $recipient instanceof User && ! $recipient instanceof Admin) {
            return DeliveryOutcome::skipped('recipient_missing');
        }

        try {
            return $sender->send($delivery, $recipient);
        } catch (Throwable $e) {
            Log::warning('notification delivery crashed', ['delivery' => $delivery->uuid, 'channel' => $delivery->channel->value, 'exception' => $e::class]);

            return DeliveryOutcome::retry('delivery_crashed', 'The channel failed unexpectedly.');
        }
    }

    private function scheduleRetry(NotificationDelivery $delivery, DeliveryOutcome $outcome): void
    {
        $backoff = array_values((array) config('notifications.retry_backoff', [30, 120, 600, 1800]));
        $seconds = (int) ($backoff[min($delivery->attempts - 1, count($backoff) - 1)] ?? 60);

        $this->deliveries->update($delivery, [
            'status' => DeliveryStatus::Queued,
            'provider' => $outcome->provider,
            'error_code' => $outcome->errorCode,
            'error_message' => $outcome->errorMessage,
            'next_attempt_at' => now()->addSeconds($seconds),
        ]);

        SendNotificationDelivery::dispatch($delivery->id)->delay(now()->addSeconds($seconds));
    }

    private function fail(NotificationDelivery $delivery, DeliveryOutcome $outcome, ?string $codeOverride = null): void
    {
        $this->deliveries->update($delivery, [
            'status' => DeliveryStatus::Failed,
            'provider' => $outcome->provider,
            'error_code' => $codeOverride ?? $outcome->errorCode,
            'error_message' => $outcome->errorMessage,
            'next_attempt_at' => null,
        ]);
    }
}
