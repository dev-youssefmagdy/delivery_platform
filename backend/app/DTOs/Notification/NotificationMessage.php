<?php

namespace App\DTOs\Notification;

use App\Enums\Identity\ClientApp;
use App\Enums\Notification\NotificationEvent;
use App\Models\Admin;
use App\Models\User;
use Closure;

/**
 * What the domain asks the notification platform to tell a person: an event, who, and the facts the template needs.
 * The platform decides the words (template + language), the channels (preferences) and the retries.
 */
final readonly class NotificationMessage
{
    /**
     * @param array<string, scalar|Closure(string): string|null> $variables values for the template placeholders (never secrets or OTPs); a closure receives the recipient's language
     * @param array<string, scalar|null> $data extra payload for the apps (ids only, no PII)
     * @param array{title: string, body: string}|null $content fixed text for a language-less campaign message
     */
    public function __construct(
        public NotificationEvent $event,
        public User|Admin $recipient,
        public ?ClientApp $app = null,
        public array $variables = [],
        public ?string $linkId = null,
        public ?string $dedupeKey = null,
        public array $data = [],
        public ?int $campaignId = null,
        public ?array $content = null,
    ) {}

    /**
     * A message to a mobile user in the app the event addresses (the audience's own app unless `$app` says otherwise).
     *
     * @param array<string, scalar|Closure(string): string|null> $variables
     */
    public static function toUser(NotificationEvent $event, User $user, ?ClientApp $app = null, array $variables = [], ?string $linkId = null, ?string $dedupeKey = null): self
    {
        return new self($event, $user, $app, $variables, $linkId, $dedupeKey);
    }

    /**
     * @param array<string, scalar|null> $variables
     */
    public static function toAdmin(NotificationEvent $event, Admin $admin, array $variables = [], ?string $linkId = null, ?string $dedupeKey = null): self
    {
        return new self($event, $admin, null, $variables, $linkId, $dedupeKey);
    }
}
