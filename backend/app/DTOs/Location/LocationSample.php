<?php

namespace App\DTOs\Location;

use App\Enums\Location\LocationSource;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;

/**
 * One validated position sample from a driver app.
 */
final readonly class LocationSample
{
    public function __construct(
        public GeoPoint $point,
        public CarbonImmutable $recordedAt,
        public ?float $accuracyM = null,
        public ?int $heading = null,
        public ?float $speedMps = null,
        public LocationSource $source = LocationSource::Gps,
    ) {}
}
