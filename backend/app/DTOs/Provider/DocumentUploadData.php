<?php

namespace App\DTOs\Provider;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * Validated input of one document upload. `vehicleUuid` is set for vehicle documents only.
 */
final readonly class DocumentUploadData
{
    public function __construct(
        public UploadedFile $file,
        public string $documentTypeCode,
        public ?string $number = null,
        public ?CarbonImmutable $issuedAt = null,
        public ?CarbonImmutable $expiresAt = null,
        public ?string $vehicleUuid = null,
    ) {}
}
