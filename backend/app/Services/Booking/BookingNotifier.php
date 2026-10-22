<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingAdjustmentStatus;
use App\Enums\Booking\BookingOverrideAction;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Common\ActorType;
use App\Enums\Notification\NotificationEvent;
use App\Models\Booking;
use App\Models\BookingAdjustmentRequest;
use App\Services\Notification\PartyNotifier;

/**
 * Notification hook of the booking module (SRS §14.1): translates what happened to a booking into notification events
 * for the customer and the provider. Pushes carry no names, addresses or amounts (lock-screen safe) and open the
 * booking. Each call is deduplicated by the booking sequence of the occurrence, so a retried flow notifies once. The apps
 * also learn the same facts from the status endpoint and realtime events; a notification is never the source of truth.
 */
class BookingNotifier
{
    public function __construct(private readonly PartyNotifier $parties) {}

    public function searchExpired(Booking $booking): void
    {
        $this->parties->customer($booking, NotificationEvent::BookingSearchExpired, (string) $booking->sequence);
    }

    /**
     * @param int|null $feeMinor the cancellation fee decided for this cancellation (the payer is read from the stored decision)
     */
    public function cancelled(Booking $booking, string $by, ?int $feeMinor): void
    {
        $discriminator = (string) $booking->sequence;

        if ($by === ActorType::Customer->value) {
            $this->parties->provider($booking, NotificationEvent::BookingCancelled, $discriminator);
        } else {
            $this->parties->customer($booking, NotificationEvent::BookingCancelled, $discriminator);
        }

        if ($feeMinor !== null && $feeMinor > 0) {
            $payer = (string) (($booking->cancellation_decision['payer'] ?? null) ?: $by);

            if ($payer === ActorType::Provider->value) {
                $this->parties->provider($booking, NotificationEvent::BookingCancellationFee, $discriminator);
            } else {
                $this->parties->customer($booking, NotificationEvent::BookingCancellationFee, $discriminator);
            }
        }
    }

    public function providerCancelledRedispatch(Booking $booking): void
    {
        $this->parties->customer($booking, NotificationEvent::BookingProviderCancelled, (string) $booking->sequence);
    }

    public function completed(Booking $booking): void
    {
        $this->parties->customer($booking, NotificationEvent::BookingCompleted, 'completed');
    }

    /**
     * A custody event of a parcel (P4.1). The customer is told about the steps that matter to them; `in_transit` is a
     * silent timeline entry. Deduplicated by the booking sequence of the event.
     */
    public function custody(Booking $booking, CustodyStatus $status): void
    {
        $event = match ($status) {
            CustodyStatus::PickedUp => NotificationEvent::DeliveryPickedUp,
            CustodyStatus::ArrivedDropoff => NotificationEvent::DeliveryArrivedDropoff,
            CustodyStatus::Delivered => NotificationEvent::DeliveryDelivered,
            CustodyStatus::Returned => NotificationEvent::DeliveryReturned,
            default => null,
        };

        if ($event !== null) {
            $this->parties->customer($booking, $event, $status->value);
        }
    }

    /**
     * A delivery attempt failed: the customer must choose what happens next.
     */
    public function deliveryAttemptFailed(Booking $booking, string $attemptUuid): void
    {
        $this->parties->customer($booking, NotificationEvent::DeliveryAttemptFailed, $attemptUuid);
    }

    /**
     * The failed attempt was resolved by `$by`: the other party learns what was decided.
     */
    public function deliveryAttemptResolved(Booking $booking, string $attemptUuid, ActorType $by): void
    {
        $this->toParty($booking, $by === ActorType::Customer ? ActorType::Provider : ActorType::Customer, NotificationEvent::DeliveryAttemptResolved, $attemptUuid);
    }

    /**
     * Too many wrong recipient codes: both sides are told that support has to step in.
     */
    public function recipientOtpLocked(Booking $booking): void
    {
        $this->parties->customer($booking, NotificationEvent::DeliveryRecipientOtpLocked, 'otp_locked');
        $this->parties->provider($booking, NotificationEvent::DeliveryRecipientOtpLocked, 'otp_locked');
    }

    /**
     * An operator intervened (SRS §8.3): the customer and, when there is one, the provider are told that support
     * updated the booking; the details are in the app.
     *
     * @param array<string, mixed> $summary non-PII facts of the action (not part of the notification text)
     */
    public function operationsOverride(Booking $booking, BookingOverrideAction $action, array $summary = []): void
    {
        $discriminator = $action->value.':'.$booking->sequence;

        $this->parties->customer($booking, NotificationEvent::BookingOperationsUpdate, $discriminator);
        $this->parties->provider($booking, NotificationEvent::BookingOperationsUpdate, $discriminator);
    }

    /**
     * A fare adjustment was requested or answered (P3.5): the other party is asked to respond, the requester learns the
     * answer. The realtime event updates an open screen; the push reaches a closed app.
     */
    public function adjustmentChanged(Booking $booking, BookingAdjustmentRequest $adjustment, string $event): void
    {
        if ($event === 'adjustment_requested') {
            $this->toParty($booking, $adjustment->requester_type === ActorType::Customer->value ? ActorType::Provider : ActorType::Customer, NotificationEvent::BookingAdjustmentRequested, $adjustment->uuid.':requested');

            return;
        }

        $outcome = match ($adjustment->status) {
            BookingAdjustmentStatus::Approved => 'approved',
            BookingAdjustmentStatus::Rejected => 'declined',
            BookingAdjustmentStatus::Expired => 'expired',
            BookingAdjustmentStatus::Voided => 'voided',
            BookingAdjustmentStatus::Withdrawn => 'withdrawn',
            BookingAdjustmentStatus::Pending => null,
        };

        if ($outcome === null) {
            return;
        }

        $requester = $adjustment->requester_type === ActorType::Customer->value ? ActorType::Customer : ActorType::Provider;
        $key = $adjustment->uuid.':'.$outcome;

        if ($adjustment->status === BookingAdjustmentStatus::Withdrawn) {
            // The requester withdrew it: the party who was asked no longer needs to answer.
            $this->toParty($booking, $adjustment->counterpartType(), NotificationEvent::BookingAdjustmentAnswered, $key, ['outcome' => $outcome]);

            return;
        }

        $this->toParty($booking, $requester, NotificationEvent::BookingAdjustmentAnswered, $key, ['outcome' => $outcome]);

        if ($adjustment->status === BookingAdjustmentStatus::Voided) {
            $this->toParty($booking, $adjustment->counterpartType(), NotificationEvent::BookingAdjustmentAnswered, $key, ['outcome' => $outcome]);
        }
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    private function toParty(Booking $booking, ActorType $party, NotificationEvent $event, string $discriminator, array $variables = []): void
    {
        if ($party === ActorType::Customer) {
            $this->parties->customer($booking, $event, $discriminator, $variables);
        } else {
            $this->parties->provider($booking, $event, $discriminator, $variables);
        }
    }
}
