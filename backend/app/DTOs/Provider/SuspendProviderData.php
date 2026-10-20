<?php

namespace App\DTOs\Provider;

use Carbon\CarbonImmutable;

/**
 * Validated input of "suspend provider": a mandatory reason, temporary (optional end) or permanent.
 */
final readonly class SuspendProviderData
{
    public function __construct(
        public string $reason,
        public bool $permanent = false,
        public ?CarbonImmutable $expiresAt = null,
    ) {}
}
