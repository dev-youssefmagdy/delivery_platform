<?php

namespace App\DTOs\Location;

/**
 * A geo-search hit: the provider position and its distance from the search centre.
 */
final readonly class NearbyProvider
{
    public function __construct(
        public ProviderPosition $position,
        public float $distanceMeters,
    ) {}
}
