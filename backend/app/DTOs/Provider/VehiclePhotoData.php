<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\VehiclePhotoKind;
use Illuminate\Http\UploadedFile;

final readonly class VehiclePhotoData
{
    public function __construct(
        public UploadedFile $file,
        public VehiclePhotoKind $kind,
    ) {}
}
