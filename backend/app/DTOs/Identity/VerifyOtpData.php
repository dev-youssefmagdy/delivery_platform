<?php

namespace App\DTOs\Identity;

use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpPurpose;

final readonly class VerifyOtpData
{
    public function __construct(
        public string $phone,
        public string $code,
        public OtpPurpose $purpose,
        public ClientApp $app,
        public DeviceData $device,
    ) {}
}
