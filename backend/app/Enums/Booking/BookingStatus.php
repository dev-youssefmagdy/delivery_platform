<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * The booking lifecycle (SRS §8.1, Plan §7.2, ADR-16). This enum owns the transition graph; only
 * `BookingTransitionService` may change `bookings.status`, and it asks `canTransitionTo()` before it does.
 * Payment, dispute and refund outcomes are separate axes: `completed` stays `completed` after a refund.
 */
enum BookingStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case SearchingOffers = 'searching_offers';
    case OffersReceived = 'offers_received';
    case ProviderSelected = 'provider_selected';
    case ProviderHeadingToPickup = 'provider_heading_to_pickup';
    case ProviderArrived = 'provider_arrived';
    case InProgress = 'in_progress';
    case DeliveryAttemptFailed = 'delivery_attempt_failed';
    case CompletedPendingPayment = 'completed_pending_payment';
    case Completed = 'completed';
    case Disputed = 'disputed';
    case CancelledByCustomer = 'cancelled_by_customer';
    case CancelledByProvider = 'cancelled_by_provider';
    case CancelledByOperations = 'cancelled_by_operations';
    case ExpiredNoProvider = 'expired_no_provider';

    /**
     * The complete transition table of Plan §7.2.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        $cancellations = [self::CancelledByCustomer, self::CancelledByProvider, self::CancelledByOperations];

        return match ($this) {
            self::Draft => [self::SearchingOffers, self::CancelledByCustomer, self::CancelledByOperations],
            self::SearchingOffers => [self::OffersReceived, self::ProviderSelected, self::ExpiredNoProvider, self::CancelledByCustomer, self::CancelledByOperations],
            self::OffersReceived => [self::SearchingOffers, self::ProviderSelected, self::ExpiredNoProvider, self::CancelledByCustomer, self::CancelledByOperations],
            self::ProviderSelected => [self::ProviderHeadingToPickup, self::SearchingOffers, ...$cancellations],
            self::ProviderHeadingToPickup => [self::ProviderArrived, self::SearchingOffers, ...$cancellations],
            self::ProviderArrived => [self::InProgress, self::SearchingOffers, ...$cancellations],
            self::InProgress => [self::CompletedPendingPayment, self::Completed, self::DeliveryAttemptFailed, self::CancelledByOperations],
            self::DeliveryAttemptFailed => [self::InProgress, self::CancelledByOperations, self::Disputed],
            self::CompletedPendingPayment => [self::Completed, self::Disputed],
            self::Completed => [self::Disputed],
            self::Disputed => [self::Completed],
            self::CancelledByCustomer, self::CancelledByProvider, self::CancelledByOperations, self::ExpiredNoProvider => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * No transition leaves this status.
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function isCancellation(): bool
    {
        return in_array($this, [self::CancelledByCustomer, self::CancelledByProvider, self::CancelledByOperations], true);
    }

    /**
     * The request is still being worked on: counted by "active booking" guards (deletion, deactivation, one active
     * on-demand booking per customer). Finished trips under dispute are not in flight.
     */
    public function isInFlight(): bool
    {
        return in_array($this, self::inFlight(), true);
    }

    /**
     * Customer-visible search phase: dispatch runs and offers may arrive.
     */
    public function isSearching(): bool
    {
        return $this === self::SearchingOffers || $this === self::OffersReceived;
    }

    /**
     * A provider holds the booking and is on the way or working it.
     */
    public function isAssigned(): bool
    {
        return in_array($this, self::assigned(), true);
    }

    /**
     * @return list<self>
     */
    public static function inFlight(): array
    {
        return [
            self::Draft, self::SearchingOffers, self::OffersReceived, self::ProviderSelected, self::ProviderHeadingToPickup,
            self::ProviderArrived, self::InProgress, self::DeliveryAttemptFailed, self::CompletedPendingPayment,
        ];
    }

    /**
     * @return list<self>
     */
    public static function assigned(): array
    {
        return [self::ProviderSelected, self::ProviderHeadingToPickup, self::ProviderArrived, self::InProgress, self::DeliveryAttemptFailed];
    }

    /**
     * @return list<self>
     */
    public static function searching(): array
    {
        return [self::SearchingOffers, self::OffersReceived];
    }

    /**
     * @return list<string>
     */
    public static function searchingValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::searching());
    }

    /**
     * @return list<string>
     */
    public static function inFlightValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::inFlight());
    }

    public function label(): string
    {
        return __("enums.booking_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::SearchingOffers, self::OffersReceived => BadgeColor::Info,
            self::ProviderSelected, self::ProviderHeadingToPickup, self::ProviderArrived, self::InProgress => BadgeColor::Primary,
            self::DeliveryAttemptFailed, self::CompletedPendingPayment, self::Disputed => BadgeColor::Warning,
            self::Completed => BadgeColor::Success,
            self::CancelledByCustomer, self::CancelledByProvider, self::CancelledByOperations, self::ExpiredNoProvider => BadgeColor::Danger,
        };
    }
}
