<?php

namespace App\Enums\Notification;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How a notification reaches a person (SRS §14.1). `in_app` is the inbox row itself.
 */
enum NotificationChannel: string implements HasLabel
{
    use EnumHelpers;

    case Push = 'push';
    case Sms = 'sms';
    case Email = 'email';
    case InApp = 'in_app';

    /**
     * Channels a person can switch off per category (all of them; essential categories lock push and in_app).
     *
     * @return list<self>
     */
    public static function preferenceChannels(): array
    {
        return self::cases();
    }

    /**
     * Channels that need a queued attempt (the inbox row is written synchronously with the event).
     */
    public function isQueued(): bool
    {
        return $this !== self::InApp;
    }

    public function label(): string
    {
        return __("enums.notification_channel.{$this->value}");
    }
}
