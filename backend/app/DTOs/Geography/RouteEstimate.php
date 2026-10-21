<?php

namespace App\DTOs\Geography;

use App\DTOs\Pricing\PriceGuidanceResult;

/**
 * Route between two positions plus the service area the trip starts in and, when a service category was asked for,
 * the reference price guidance for the trip (P2.3): guidance only, never a quote.
 */
final readonly class RouteEstimate
{
    public function __construct(public RouteData $route, public ResolvedArea $origin, public ?PriceGuidanceResult $pricing = null) {}
}
