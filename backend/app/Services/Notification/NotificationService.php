<?php

namespace App\Services\Notification;

use App\DTOs\Notification\NotificationMessage;
use App\Enums\Common\Locale;
use App\Enums\Identity\ClientApp;
use App\Enums\Notification\DeliveryStatus;
use App\Enums\Notification\NotificationAudience;
use App\Enums\Notification\NotificationChannel;
use App\Jobs\Notification\SendNotificationDelivery;
use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\User;
use App\Repositories\Contracts\Notification\AppNotificationRepositoryInterface;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The one entry point of the notification platform (SRS §14.1, ADR-12). The domain says WHAT happened and to WHOM;
 * this service decides the words (template in the person's language, Arabic fallback), the channels (the event's
 * channels narrowed by the person's preferences, essential categories locked on), writes the inbox entry and one
 * delivery per channel, and queues the sends after the surrounding transaction commits.
 *
 * Sending never breaks the caller: any failure here is reported and swallowed, the business action stands. The same
 * dedupe key for the same person is the same notification: it is stored and pushed once.
 */
final class NotificationService
{
    public function __construct(
        private readonly TemplateResolver $templates,
        private readonly TemplateRenderer $renderer,
        private readonly NotificationPreferenceService $preferences,
        private readonly AppNotificationRepositoryInterface $inbox,
        private readonly NotificationDeliveryRepositoryInterface $deliveries,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return AppNotification|null the inbox entry (new or the earlier one for the same dedupe key), null when nothing was created
     */
    public function send(NotificationMessage $message): ?AppNotification
    {
        try {
            return $this->dispatch($message);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @param iterable<NotificationMessage> $messages
     */
    public function sendMany(iterable $messages): void
    {
        foreach ($messages as $message) {
            $this->send($message);
        }
    }

    private function dispatch(NotificationMessage $message): ?AppNotification
    {
        $recipient = $message->recipient;
        $app = $this->resolveApp($message);

        if ($recipient instanceof User && ($recipient->isAnonymised() || $recipient->trashed())) {
            return null;
        }

        if (! $message->event->audience()->allows($app)) {
            Log::warning('notification skipped: the event does not address this app', ['event' => $message->event->value, 'app' => $app?->value]);

            return null;
        }

        $type = $recipient instanceof Admin ? 'admin' : 'user';

        if ($message->dedupeKey !== null && ($existing = $this->inbox->findByDedupeKey($type, $recipient->id, $message->dedupeKey)) !== null) {
            return $existing;
        }

        $locale = $this->localeOf($recipient);
        $channels = $this->channelsFor($message, $recipient);
        $inboxText = $this->content($message, NotificationChannel::InApp, $locale);

        if ($inboxText === null) {
            Log::warning('notification skipped: no template', ['event' => $message->event->value]);

            return null;
        }

        try {
            /** @var array{0: AppNotification, 1: list<int>} $created */
            $created = $this->db->transaction(fn (): array => $this->store($message, $type, $app, $locale, $channels, $inboxText));
        } catch (UniqueConstraintViolationException) {
            // A concurrent request stored the same dedupe key between our check and the insert: that one wins.
            return $message->dedupeKey === null ? null : $this->inbox->findByDedupeKey($type, $recipient->id, $message->dedupeKey);
        }

        [$notification, $queuedIds] = $created;

        foreach ($queuedIds as $deliveryId) {
            SendNotificationDelivery::dispatch($deliveryId)->afterCommit();
        }

        return $notification;
    }

    /**
     * @param list<NotificationChannel> $channels
     * @param array{title: string|null, body: string, template_id: int|null, version: int|null} $inboxText
     * @return array{0: AppNotification, 1: list<int>}
     */
    private function store(NotificationMessage $message, string $type, ?ClientApp $app, string $locale, array $channels, array $inboxText): array
    {
        $event = $message->event;

        /** @var AppNotification $notification */
        $notification = $this->inbox->create([
            'recipient_type' => $type,
            'recipient_id' => $message->recipient->id,
            'app' => $app?->value,
            'event_key' => $event->value,
            'category' => $event->category()->value,
            'title' => (string) $inboxText['title'],
            'body' => $inboxText['body'],
            'locale' => $locale,
            'link_type' => $message->linkId === null && $event->linkType()->value !== 'inbox' ? null : $event->linkType()->value,
            'link_id' => $message->linkId,
            'data' => $message->data === [] ? null : $message->data,
            'dedupe_key' => $message->dedupeKey,
            'campaign_id' => $message->campaignId,
        ]);

        $queued = [];

        foreach ($channels as $channel) {
            $text = $channel === NotificationChannel::InApp ? $inboxText : $this->content($message, $channel, $locale);

            if ($text === null) {
                continue;
            }

            $delivery = $this->deliveries->create([
                'notification_id' => $notification->id,
                'recipient_type' => $type,
                'recipient_id' => $message->recipient->id,
                'event_key' => $event->value,
                'channel' => $channel->value,
                'status' => $channel->isQueued() ? DeliveryStatus::Queued->value : DeliveryStatus::Delivered->value,
                'provider' => $channel->isQueued() ? null : 'inbox',
                'attempts' => $channel->isQueued() ? 0 : 1,
                'template_id' => $text['template_id'],
                'template_version' => $text['version'],
                'title' => $text['title'],
                'body' => $text['body'],
                'sent_at' => $channel->isQueued() ? null : now(),
                'delivered_at' => $channel->isQueued() ? null : now(),
            ]);

            if ($channel->isQueued()) {
                $queued[] = $delivery->id;
            }
        }

        return [$notification, $queued];
    }

    /**
     * @return list<NotificationChannel> the event's channels narrowed by the person's choices (the inbox is always kept)
     */
    private function channelsFor(NotificationMessage $message, User|Admin $recipient): array
    {
        $event = $message->event;
        $candidates = $event->defaultChannels();

        if ($message->campaignId !== null) {
            $candidates = [NotificationChannel::InApp, NotificationChannel::Push];
        }

        if (! $recipient instanceof User) {
            return $candidates;
        }

        return $this->preferences->allowedChannels($recipient, $event->category(), $candidates);
    }

    /**
     * @return array{title: string|null, body: string, template_id: int|null, version: int|null}|null
     */
    private function content(NotificationMessage $message, NotificationChannel $channel, string $locale): ?array
    {
        if ($message->content !== null) {
            return ['title' => $message->content['title'], 'body' => $message->content['body'], 'template_id' => null, 'version' => null];
        }

        $template = $this->templates->resolve($message->event, $channel, $locale);

        if ($template === null) {
            return null;
        }

        $variables = [];

        foreach ($message->variables as $name => $value) {
            $value = $value instanceof \Closure ? $value($locale) : $value;
            $variables[$name] = $name === 'outcome' && is_string($value) ? $this->templates->variableValue($name, $value, $locale) : $value;
        }

        return [
            'title' => $template->title === null ? null : $this->renderer->render($template->title, $variables),
            'body' => $this->renderer->render($template->body, $variables),
            'template_id' => $template->templateId,
            'version' => $template->version,
        ];
    }

    private function resolveApp(NotificationMessage $message): ?ClientApp
    {
        if ($message->recipient instanceof Admin) {
            return null;
        }

        if ($message->app !== null) {
            return $message->app;
        }

        return match ($message->event->audience()) {
            NotificationAudience::Provider => ClientApp::Provider,
            default => ClientApp::Customer,
        };
    }

    private function localeOf(User|Admin $recipient): string
    {
        $preferred = $recipient instanceof HasLocalePreference ? $recipient->preferredLocale() : null;

        return Locale::tryFromValue($preferred)?->value ?? Locale::default()->value;
    }
}
