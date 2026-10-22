<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Groups of notifications a person can control (SRS §14.1). Essential categories (booking, account, payments, safety)
 * can never be fully disabled: push and the inbox stay on so a person cannot miss a trip, a fee or a safety update.
 * The inbox itself is never switchable.
 * Marketing is the only category that needs an explicit opt-in.
 */
enum NotificationCategory: string implements HasLabel
{
    use EnumHelpers;

    case Booking = 'booking';
    case Offers = 'offers';
    case Account = 'account';
    case Payments = 'payments';
    case Payouts = 'payouts';
    case Promotions = 'promotions';
    case Support = 'support';
    case Safety = 'safety';
    case Marketing = 'marketing';
    case System = 'system';

    public function isEssential(): bool
    {
        return in_array($this, [self::Booking, self::Account, self::Payments, self::Safety], true);
    }

    public function requiresOptIn(): bool
    {
        return $this === self::Marketing;
    }

    /**
     * Categories a mobile person sees on the preferences screen (system is for admins only).
     *
     * @return list<self>
     */
    public static function forUsers(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $category): bool => $category !== self::System));
    }

    /**
     * Whether a channel stays on for the category whatever the person chose: the inbox always, and push for essential
     * categories.
     */
    public function locksChannel(NotificationChannel $channel): bool
    {
        return $channel === NotificationChannel::InApp || ($this->isEssential() && $channel === NotificationChannel::Push);
    }

    /**
     * The value used before the person chooses (marketing starts off; everything else on).
     */
    public function defaultEnabled(NotificationChannel $channel): bool
    {
        if ($this->requiresOptIn()) {
            return false;
        }

        // Text messages and emails cost money / are noisy: on by default only for essential categories.
        return match ($channel) {
            NotificationChannel::Push, NotificationChannel::InApp => true,
            NotificationChannel::Sms, NotificationChannel::Email => $this->isEssential() || $this === self::Payouts,
        };
    }

    public function label(): string
    {
        return __("enums.notification_category.{$this->value}");
    }

    public function description(): string
    {
        return __("enums.notification_category_hint.{$this->value}");
    }
}
