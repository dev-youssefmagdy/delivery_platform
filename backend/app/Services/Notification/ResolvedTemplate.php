<?php

namespace App\Services\Notification;

/**
 * The text chosen for an event, channel and language (before the variables are filled in).
 */
final readonly class ResolvedTemplate
{
    public function __construct(
        public ?string $title,
        public string $body,
        public string $locale,
        public ?int $templateId = null,
        public ?int $version = null,
    ) {}
}
