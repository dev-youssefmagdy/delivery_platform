<?php

namespace App\Services\Notification;

use App\Enums\Common\Locale;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;
use Illuminate\Contracts\Translation\Loader;

/**
 * Picks the words for an event on a channel: the active template in the person's language, else the active Arabic one
 * (Arabic is the platform fallback, SRS §3), else the built-in default text shipped in `lang/{locale}/notifications.php`
 * (so a fresh install or a deactivated template still sends something sensible). The inbox shares the push text unless
 * an `in_app` template exists.
 */
final class TemplateResolver
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $templates,
        private readonly Loader $loader,
    ) {}

    public function resolve(NotificationEvent $event, NotificationChannel $channel, string $locale): ?ResolvedTemplate
    {
        $channels = $channel === NotificationChannel::InApp ? [NotificationChannel::InApp, NotificationChannel::Push] : [$channel];
        $locales = array_values(array_unique([$locale, Locale::default()->value]));

        foreach ($locales as $candidate) {
            foreach ($channels as $source) {
                $template = $this->templates->findFor($event->value, $source, $candidate);

                if ($template !== null && $template->active) {
                    return new ResolvedTemplate($template->title, $template->body, $candidate, $template->id, $template->version);
                }
            }
        }

        foreach ($locales as $candidate) {
            foreach ($channels as $source) {
                $default = $this->defaultFor($event, $source, $candidate);

                if ($default !== null) {
                    return new ResolvedTemplate($default['title'], $default['body'], $candidate);
                }
            }
        }

        return null;
    }

    /**
     * A coded variable value (for example `outcome=approved`) in the person's language; other values pass through.
     */
    public function variableValue(string $name, string $value, string $locale): string
    {
        $words = $this->loader->load($locale, 'notifications')['_values'][$name] ?? null;

        return is_array($words) && isset($words[$value]) ? (string) $words[$value] : $value;
    }

    /**
     * The built-in text of an event on a channel, or null when the event has none for it.
     *
     * @return array{title: string|null, body: string}|null
     */
    public function defaultFor(NotificationEvent $event, NotificationChannel $channel, string $locale): ?array
    {
        $entry = $this->loader->load($locale, 'notifications')[$event->value] ?? null;

        if (! is_array($entry)) {
            return null;
        }

        return match ($channel) {
            NotificationChannel::Push, NotificationChannel::InApp => isset($entry['title'], $entry['body']) ? ['title' => (string) $entry['title'], 'body' => (string) $entry['body']] : null,
            NotificationChannel::Sms => isset($entry['sms']) ? ['title' => null, 'body' => (string) $entry['sms']] : null,
            NotificationChannel::Email => isset($entry['email_subject'], $entry['email_body']) ? ['title' => (string) $entry['email_subject'], 'body' => (string) $entry['email_body']] : null,
        };
    }
}
