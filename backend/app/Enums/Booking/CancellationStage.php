<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How far the trip had come when somebody cancelled (SRS §8.4): fee rules of a cancellation policy are per stage.
 * Before a provider is selected nothing is owed, so `searching` never carries a fee.
 */
enum CancellationStage: string implements HasLabel
{
    use EnumHelpers;

    case Searching = 'searching';
    case ProviderSelected = 'provider_selected';
    case HeadingToPickup = 'heading_to_pickup';
    case Arrived = 'arrived';
    /** Not a trip stage: the fee rule of a reported no-show (customer after the free wait, provider after the report window). */
    case NoShow = 'no_show';

    public static function of(BookingStatus $status): ?self
    {
        return match ($status) {
            BookingStatus::Draft, BookingStatus::SearchingOffers, BookingStatus::OffersReceived => self::Searching,
            BookingStatus::ProviderSelected => self::ProviderSelected,
            BookingStatus::ProviderHeadingToPickup => self::HeadingToPickup,
            BookingStatus::ProviderArrived => self::Arrived,
            default => null,
        };
    }

    /**
     * Stages a fee rule can be written for.
     *
     * @return list<self>
     */
    public static function chargeable(): array
    {
        return [self::ProviderSelected, self::HeadingToPickup, self::Arrived, self::NoShow];
    }

    public function label(): string
    {
        return __("enums.cancellation_stage.{$this->value}");
    }
}
