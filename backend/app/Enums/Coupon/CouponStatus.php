<?php

namespace App\Enums\Coupon;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Coupon lifecycle (SRS §11.1). `allowedTransitions()` is the one graph: the admin actions and the scheduled
 * `coupons:sync-status` command both go through CouponLifecycleService, which enforces it.
 */
enum CouponStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case Exhausted = 'exhausted';
    case Expired = 'expired';
    case Archived = 'archived';

    public function label(): string
    {
        return __("enums.coupon_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Draft => BadgeColor::Neutral,
            self::Scheduled => BadgeColor::Info,
            self::Active => BadgeColor::Success,
            self::Paused, self::Exhausted => BadgeColor::Warning,
            self::Expired, self::Archived => BadgeColor::Neutral,
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Scheduled, self::Active, self::Archived],
            self::Scheduled => [self::Active, self::Paused, self::Expired, self::Archived],
            self::Active => [self::Paused, self::Exhausted, self::Expired, self::Archived],
            self::Paused => [self::Active, self::Scheduled, self::Expired, self::Archived],
            self::Exhausted => [self::Active, self::Expired, self::Archived],
            self::Expired => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Customers can redeem the coupon only while it is active.
     */
    public function isRedeemable(): bool
    {
        return $this === self::Active;
    }

    /**
     * Financial terms of a coupon that is not a draft create a new version when edited.
     */
    public function isLive(): bool
    {
        return ! in_array($this, [self::Draft], true);
    }
}
