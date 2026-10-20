<?php

namespace App\DTOs\Geography;

/**
 * Route between two positions plus the service area the trip starts in. Pricing is added by the booking
 * milestones (P2.3); this is distance, time and the polyline only.
 */
final readonly class RouteEstimate
{
    public function __construct(public RouteData $route, public ResolvedArea $origin) {}
}
