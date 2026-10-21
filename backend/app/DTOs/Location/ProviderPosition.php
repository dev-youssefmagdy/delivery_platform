<?php

namespace App\DTOs\Location;

use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;

/**
 * What the geo index knows about an online provider: where, how fresh, and which categories it can serve.
 */
final readonly class ProviderPosition
{
    /**
     * @param list<int> $vehicleCategoryIds
     * @param list<int> $serviceCategoryIds
     */
    public function __construct(
        public int $providerId,
        public int $cityId,
        public GeoPoint $point,
        public CarbonImmutable $recordedAt,
        public ?float $accuracyM = null,
        public ?int $heading = null,
        public ?float $speedMps = null,
        public array $vehicleCategoryIds = [],
        public array $serviceCategoryIds = [],
    ) {}
}
