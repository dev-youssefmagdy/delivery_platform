<?php

namespace App\DTOs\Notification;

use App\Enums\Notification\NotificationChannel;
use Illuminate\Http\Request;

/**
 * Input of the template form: the text and the active flag. Event, channel and language are only read when a template is
 * created; they never change afterwards.
 */
final readonly class NotificationTemplateData
{
    public function __construct(
        public ?string $title,
        public string $body,
        public bool $active,
        public ?int $lockVersion = null,
        public ?string $eventKey = null,
        public ?NotificationChannel $channel = null,
        public ?string $locale = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $title = trim((string) $request->input('title'));

        return new self(
            title: $title === '' ? null : $title,
            body: trim((string) $request->input('body')),
            active: $request->boolean('active'),
            lockVersion: $request->filled('lock_version') ? $request->integer('lock_version') : null,
            eventKey: $request->filled('event_key') ? (string) $request->input('event_key') : null,
            channel: $request->filled('channel') ? NotificationChannel::tryFrom((string) $request->input('channel')) : null,
            locale: $request->filled('locale') ? (string) $request->input('locale') : null,
        );
    }
}
