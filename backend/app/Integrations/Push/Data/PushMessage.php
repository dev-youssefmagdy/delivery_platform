<?php

namespace App\Integrations\Push\Data;

/**
 * One push to one device token. Title and body are what the lock screen shows (kept minimal by the templates); `data`
 * carries the deep-link payload and must hold strings only (FCM requirement).
 */
final readonly class PushMessage
{
    /**
     * @param array<string, string> $data
     */
    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array $data = [],
        public ?string $collapseKey = null,
        public bool $highPriority = true,
    ) {}
}
