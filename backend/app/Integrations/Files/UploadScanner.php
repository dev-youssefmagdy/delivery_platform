<?php

namespace App\Integrations\Files;

use App\Enums\Provider\FileScanStatus;
use App\Integrations\Files\Contracts\FileScanner;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;

/**
 * Applies the scanner policy for uploads: with a driver configured the verdict is the driver's; with none
 * configured the file is stored as `not_scanned` and the review screen shows that clearly (never a fake "clean").
 */
final class UploadScanner
{
    public function __construct(private readonly Container $container) {}

    public function scan(string $path): FileScanResult
    {
        if (config('integrations.file_scanner.driver') === null) {
            Log::warning('File scanner is not configured; the upload is stored unscanned.');

            return new FileScanResult(FileScanStatus::NotScanned);
        }

        return $this->container->make(FileScanner::class)->scan($path);
    }
}
