<?php

namespace App\Integrations\Files;

use App\Enums\Provider\FileScanStatus;

/**
 * Verdict of a file scan. `Skipped` is what the noop driver returns: it never claims the file is clean.
 */
final readonly class FileScanResult
{
    public function __construct(
        public FileScanStatus $status,
        public ?string $signature = null,
    ) {}

    public function isInfected(): bool
    {
        return $this->status === FileScanStatus::Infected;
    }
}
