<?php

namespace App\DTOs\Notification;

use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;

/**
 * One requested change of a person's notification settings.
 */
final readonly class NotificationPreferenceUpdate
{
    public function __construct(
        public NotificationCategory $category,
        public NotificationChannel $channel,
        public bool $enabled,
    ) {}
}
