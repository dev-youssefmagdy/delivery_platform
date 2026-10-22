<?php

namespace App\Support\Chat;

use App\Enums\Booking\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * When a booking's chat is open (SRS §14.2): only between the customer and the selected provider, from selection until
 * the trip ended plus the configured window (`support.chat.window_after_close_minutes`). Before a provider is selected, or
 * after the booking went back to searching, there is no conversation.
 */
final class ChatWindow
{
    public static function isOpen(Booking $booking, ?CarbonInterface $now = null): bool
    {
        if (! (bool) config('support.chat.enabled', true) || $booking->provider_id === null) {
            return false;
        }

        if ($booking->status->isAssigned()) {
            return true;
        }

        $closedAt = self::closedAt($booking);

        if ($closedAt === null || ! self::hadProvider($booking)) {
            return false;
        }

        return $closedAt->addMinutes((int) config('support.chat.window_after_close_minutes', 120))->isAfter($now ?? CarbonImmutable::now());
    }

    /**
     * The conversation exists (a provider was selected at some point and still holds the booking) even if sending is closed.
     */
    public static function hasConversation(Booking $booking): bool
    {
        return $booking->provider_id !== null && self::hadProvider($booking);
    }

    public static function closesAt(Booking $booking): ?CarbonImmutable
    {
        if (! self::isOpen($booking)) {
            return null;
        }

        $closedAt = self::closedAt($booking);

        return $closedAt?->addMinutes((int) config('support.chat.window_after_close_minutes', 120));
    }

    private static function hadProvider(Booking $booking): bool
    {
        return in_array($booking->status, [
            BookingStatus::ProviderSelected, BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived, BookingStatus::InProgress,
            BookingStatus::DeliveryAttemptFailed, BookingStatus::CompletedPendingPayment, BookingStatus::Completed, BookingStatus::Disputed,
            BookingStatus::CancelledByCustomer, BookingStatus::CancelledByProvider, BookingStatus::CancelledByOperations,
        ], true);
    }

    private static function closedAt(Booking $booking): ?CarbonImmutable
    {
        $at = $booking->completed_at ?? $booking->cancelled_at;

        if ($at === null && in_array($booking->status, [BookingStatus::CompletedPendingPayment, BookingStatus::Disputed], true)) {
            $at = $booking->updated_at;
        }

        return $at === null ? null : CarbonImmutable::instance($at);
    }
}
