<?php

namespace App\Services\Booking;

use App\Enums\Booking\BookingOverrideAction;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;

/**
 * Notification hook of the booking module. Until the notification platform (P3.6) it writes to the log channel only:
 * nothing is sent to the customer, and the log lines say so. The customer still learns the outcome from the app
 * (status endpoint, realtime event); P3.6 replaces the bodies with push / in-app notifications.
 */
class BookingNotifier
{
    public function searchExpired(Booking $booking): void
    {
        Log::info('booking.search_expired (notification stub, nothing sent)', [
            'booking' => $booking->uuid,
            'customer' => $booking->customer_id,
        ]);
    }

    public function cancelled(Booking $booking, string $by, ?int $feeMinor): void
    {
        Log::info('booking.cancelled (notification stub, nothing sent)', ['booking' => $booking->uuid, 'by' => $by, 'fee_minor' => $feeMinor]);
    }

    public function providerCancelledRedispatch(Booking $booking): void
    {
        Log::info('booking.provider_cancelled_redispatch (notification stub, nothing sent)', ['booking' => $booking->uuid]);
    }

    public function completed(Booking $booking): void
    {
        Log::info('booking.completed (notification stub, nothing sent)', ['booking' => $booking->uuid]);
    }

    /**
     * An operator intervened (SRS §8.3): the customer and, when there is one, the provider are told what happened. Until
     * the notification platform (P3.6) this is a log line per party that says nothing was sent; the apps still learn the
     * outcome from the booking status / realtime events.
     *
     * @param array<string, mixed> $summary non-PII facts of the action (status, extension, fee)
     */
    public function operationsOverride(Booking $booking, BookingOverrideAction $action, array $summary = []): void
    {
        $parties = ['customer' => $booking->customer_id] + ($booking->provider_id === null ? [] : ['provider' => $booking->provider_id]);

        foreach ($parties as $party => $id) {
            Log::info('booking.operations_override (notification stub, nothing sent)', [
                'booking' => $booking->uuid,
                'action' => $action->value,
                'party' => $party,
                'party_id' => $id,
            ] + $summary);
        }
    }

    /**
     * Operations contacts the customer of a booking that found no provider (no-offer queue). Logged only until P3.6.
     */
    public function contactCustomer(Booking $booking, string $channelHint): void
    {
        Log::info('booking.operations_contact_customer (notification stub, nothing sent)', ['booking' => $booking->uuid, 'customer' => $booking->customer_id, 'channel' => $channelHint]);
    }
}
