<?php

namespace App\DTOs\Notification;

use Illuminate\Http\Request;

/**
 * Input of the template form: the text and the active flag. Event, channel and language are fixed when a template is
 * created and never change afterwards.
 */
final readonly class NotificationTemplateData
{
    public function __construct(
        public ?string $title,
        public string $body,
        public bool $active,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $title = trim((string) $request->input('title'));

        return new self(
            title: $title === '' ? null : $title,
            body: trim((string) $request->input('body')),
            active: $request->boolean('active'),
            lockVersion: $request->filled('lock_version') ? $request->integer('lock_version') : null,
        );
    }
}
