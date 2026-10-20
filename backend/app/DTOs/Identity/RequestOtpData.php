<?php

namespace App\DTOs\Identity;

use App\Enums\Identity\ClientApp;
use App\Enums\Identity\OtpPurpose;

final readonly class RequestOtpData
{
    public function __construct(
        public string $phone,
        public OtpPurpose $purpose,
        public ClientApp $app,
        public ?string $ip = null,
        public ?string $deviceId = null,
    ) {}
}
