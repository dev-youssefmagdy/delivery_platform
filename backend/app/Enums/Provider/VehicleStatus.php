<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Lifecycle of a provider's vehicle. Only `approved` vehicles can serve jobs.
 */
enum VehicleStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __("enums.vehicle_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft, self::Inactive => BadgeColor::Neutral,
            self::UnderReview => BadgeColor::Info,
            self::Approved => BadgeColor::Success,
            self::Rejected => BadgeColor::Danger,
            self::Suspended => BadgeColor::Warning,
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::UnderReview, self::Inactive],
            self::UnderReview => [self::Approved, self::Rejected],
            self::Approved => [self::Suspended, self::Inactive, self::UnderReview],
            self::Rejected => [self::UnderReview, self::Inactive],
            self::Suspended => [self::Approved, self::Inactive],
            self::Inactive => [self::Draft],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * The provider may change the vehicle's data and photos only before it is (re)submitted.
     */
    public function isEditableByProvider(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }
}
