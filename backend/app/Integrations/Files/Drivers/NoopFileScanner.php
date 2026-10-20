<?php

namespace App\Integrations\Files\Drivers;

use App\Enums\Provider\FileScanStatus;
use App\Integrations\Files\Contracts\FileScanner;
use App\Integrations\Files\FileScanResult;

/**
 * Does not scan. Allowed only in local/testing (IntegrationGuard); it answers `skipped`, never `clean`, so a stored
 * document says honestly that no antivirus looked at it.
 */
final class NoopFileScanner implements FileScanner
{
    public function scan(string $path): FileScanResult
    {
        return new FileScanResult(FileScanStatus::Skipped);
    }

    public function name(): string
    {
        return 'noop';
    }
}
