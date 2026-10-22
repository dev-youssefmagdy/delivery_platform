<?php

namespace App\Services\Notification;

use App\Enums\Common\Locale;
use App\Enums\Notification\NotificationAudience;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;

/**
 * Creates the missing templates from the built-in texts in `lang/{locale}/notifications.php`. Existing templates are never
 * touched, so edits made in the admin panel survive a deployment. Safe to run repeatedly.
 */
final class TemplateSeedingService
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $templates,
        private readonly TemplateResolver $resolver,
    ) {}

    /**
     * @return int how many templates were created
     */
    public function sync(): int
    {
        $created = 0;

        foreach (NotificationEvent::cases() as $event) {
            $channels = [$event->audience() === NotificationAudience::Admin ? NotificationChannel::InApp : NotificationChannel::Push];

            foreach ([NotificationChannel::Sms, NotificationChannel::Email] as $extra) {
                if (in_array($extra, $event->defaultChannels(), true)) {
                    $channels[] = $extra;
                }
            }

            foreach ($channels as $channel) {
                foreach (Locale::cases() as $locale) {
                    if ($this->templates->findFor($event->value, $channel, $locale->value) !== null) {
                        continue;
                    }

                    $default = $this->resolver->defaultFor($event, $channel === NotificationChannel::InApp ? NotificationChannel::Push : $channel, $locale->value);

                    if ($default === null) {
                        continue;
                    }

                    $this->templates->create([
                        'event_key' => $event->value,
                        'channel' => $channel->value,
                        'locale' => $locale->value,
                        'title' => $default['title'],
                        'body' => $default['body'],
                    ]);
                    $created++;
                }
            }
        }

        return $created;
    }
}
