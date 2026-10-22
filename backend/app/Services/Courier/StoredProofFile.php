<?php

namespace App\Services\Courier;

use App\Enums\Provider\FileScanStatus;

/**
 * A proof picture that passed inspection and the scanner and now sits on the private disk.
 */
final readonly class StoredProofFile
{
    public function __construct(
        public string $disk,
        public string $path,
        public string $sha256,
        public string $mime,
        public int $sizeBytes,
        public FileScanStatus $scanStatus,
    ) {}
}
