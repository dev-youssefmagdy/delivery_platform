<?php

namespace App\DTOs\Safety;

use Carbon\CarbonImmutable;

/**
 * A GPS position as the app reported it (never trusted blindly: it is a snapshot for staff, not a business input).
 */
final readonly class LocationFix
{
    public function __construct(
        public float $lat,
        public float $lng,
        public ?float $accuracyMeters = null,
        public ?CarbonImmutable $capturedAt = null,
    ) {}
}
