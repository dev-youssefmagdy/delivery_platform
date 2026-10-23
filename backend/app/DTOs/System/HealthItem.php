<?php

namespace App\DTOs\System;

use App\Enums\Common\HealthStatus;
use App\Enums\System\HealthArea;
use Carbon\CarbonInterface;

/**
 * One line of the admin health page. `details` are plain facts (driver name, sizes, ages): never secrets, tokens or URLs with
 * credentials. `lastError` is a safe code (exception class or error code), never a provider message.
 */
final readonly class HealthItem
{
    /**
     * @param array<string, scalar|null> $details
     */
    public function __construct(
        public HealthArea $area,
        public HealthStatus $status,
        public array $details = [],
        public ?string $lastError = null,
        public ?CarbonInterface $lastErrorAt = null,
    ) {}
}
