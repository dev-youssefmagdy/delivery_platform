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
     * Channels a person can choose per category. The inbox is not one of them: it is the record of what happened and
     * is always kept.
     *
     * @return list<self>
     */
    public static function preferenceChannels(): array
    {
        return [self::Push, self::Sms, self::Email];
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
