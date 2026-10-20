<?php

namespace App\DTOs\Identity;

use App\Enums\Common\Platform;

final readonly class DeviceData
{
    public function __construct(
        public string $deviceId,
        public Platform $platform,
        public ?string $appVersion = null,
        public ?string $pushToken = null,
        public ?string $locale = null,
    ) {}
}
