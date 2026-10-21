<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * The offer lifecycle (SRS §8.2, Plan §7.2). `submitted` and `counter_offered` are the two active statuses; every
 * other status is final. Only the offer services change it.
 */
enum OfferStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Submitted = 'submitted';
    case CounterOffered = 'counter_offered';
    case AcceptedByCustomer = 'accepted_by_customer';
    case RejectedByCustomer = 'rejected_by_customer';
    case WithdrawnByProvider = 'withdrawn_by_provider';
    case Expired = 'expired';
    case InvalidatedRequestChanged = 'invalidated_request_changed';
    case InvalidatedProviderUnavailable = 'invalidated_provider_unavailable';
    case LostBookingAssigned = 'lost_booking_assigned';

    public function isActive(): bool
    {
        return $this === self::Submitted || $this === self::CounterOffered;
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Submitted, self::CounterOffered];
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::active());
    }

    /**
     * What the driver's app shows (SRS §20.4): pending / selected / rejected / expired / invalidated.
     */
    public function providerView(): string
    {
        return match ($this) {
            self::Submitted, self::CounterOffered => 'pending',
            self::AcceptedByCustomer => 'selected',
            self::RejectedByCustomer, self::LostBookingAssigned => 'rejected',
            self::WithdrawnByProvider => 'withdrawn',
            self::Expired => 'expired',
            self::InvalidatedRequestChanged, self::InvalidatedProviderUnavailable => 'invalidated',
        };
    }

    public function label(): string
    {
        return __("enums.offer_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Submitted, self::CounterOffered => BadgeColor::Info,
            self::AcceptedByCustomer => BadgeColor::Success,
            self::RejectedByCustomer, self::LostBookingAssigned => BadgeColor::Neutral,
            self::WithdrawnByProvider, self::Expired => BadgeColor::Warning,
            self::InvalidatedRequestChanged, self::InvalidatedProviderUnavailable => BadgeColor::Danger,
        };
    }
}
