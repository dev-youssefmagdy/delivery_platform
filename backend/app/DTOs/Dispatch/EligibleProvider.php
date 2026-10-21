<?php

namespace App\DTOs\Dispatch;

use App\Models\ProviderProfile;

final readonly class EligibleProvider
{
    public function __construct(
        public ProviderProfile $profile,
        public int $vehicleId,
        public int $distanceMeters,
    ) {}
}
