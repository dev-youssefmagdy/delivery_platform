<?php

namespace App\Services\Notification;

use App\DTOs\Notification\NotificationMessage;
use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationEvent;
use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\User;
use Closure;

/**
 * Addresses notifications to the people behind a booking or a provider profile, so the domain services only say which
 * event happened to whom. Every call needs a discriminator (offer uuid, status sequence, adjustment uuid ...): it becomes
 * the dedupe key, so a repeated call for the same occurrence notifies once.
 */
final class PartyNotifier
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * @param array<string, scalar|Closure(string): string|null> $variables `code` is added automatically
     */
    public function customer(Booking $booking, NotificationEvent $event, string $discriminator, array $variables = []): void
    {
        $booking->loadMissing('customer');
        $this->notifications->send(NotificationMessage::toUser(
            $event,
            $booking->customer,
            ClientApp::Customer,
            ['code' => $booking->code] + $variables,
            $booking->uuid,
            $this->key($event, $booking->uuid, $discriminator),
        ));
    }

    /**
     * @param array<string, scalar|Closure(string): string|null> $variables
     */
    public function provider(Booking $booking, NotificationEvent $event, string $discriminator, array $variables = [], ?ProviderProfile $profile = null): void
    {
        $profile ??= $booking->provider_id === null ? null : $booking->loadMissing('provider.user')->provider;

        if (! $profile instanceof ProviderProfile) {
            return;
        }

        $profile->loadMissing('user');
        $this->notifications->send(NotificationMessage::toUser(
            $event,
            $profile->user,
            ClientApp::Provider,
            ['code' => $booking->code] + $variables,
            $booking->uuid,
            $this->key($event, $booking->uuid, $discriminator),
        ));
    }

    /**
     * A push to a provider about a booking the provider is not assigned to yet (a new nearby request).
     */
    public function providerRequest(ProviderProfile $profile, NotificationEvent $event, string $bookingUuid, string $discriminator): void
    {
        $profile->loadMissing('user');
        $this->notifications->send(NotificationMessage::toUser(
            $event,
            $profile->user,
            ClientApp::Provider,
            [],
            $bookingUuid,
            $this->key($event, $bookingUuid.':'.$profile->uuid, $discriminator),
        ));
    }

    /**
     * A notification about the provider account itself (application, documents, payouts).
     *
     * @param array<string, scalar|Closure(string): string|null> $variables
     */
    public function providerAccount(ProviderProfile $profile, NotificationEvent $event, string $discriminator, array $variables = [], ?string $linkId = null): void
    {
        $profile->loadMissing('user');
        $this->notifications->send(NotificationMessage::toUser(
            $event,
            $profile->user,
            ClientApp::Provider,
            $variables,
            $linkId ?? $profile->uuid,
            $this->key($event, $profile->uuid, $discriminator),
        ));
    }

    /**
     * @param array<string, scalar|Closure(string): string|null> $variables
     */
    public function user(User $user, ClientApp $app, NotificationEvent $event, string $discriminator, array $variables = [], ?string $linkId = null): void
    {
        $this->notifications->send(NotificationMessage::toUser(
            $event,
            $user,
            $app,
            $variables,
            $linkId,
            $this->key($event, $user->uuid, $discriminator),
        ));
    }

    private function key(NotificationEvent $event, string $subject, string $discriminator): string
    {
        return mb_substr($event->value.':'.$subject.':'.$discriminator, 0, 120);
    }
}
