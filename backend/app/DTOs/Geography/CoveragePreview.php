<?php

namespace App\DTOs\Geography;

/**
 * Admin "coverage preview": what the platform would do for a point or a two-point trip.
 */
final readonly class CoveragePreview
{
    /**
     * @param string|null $routeError stable error code when the route could not be estimated (`maps_unavailable`, `route_not_found`, ...)
     */
    public function __construct(
        public ResolvedArea $origin,
        public ?ResolvedArea $destination = null,
        public ?RouteData $route = null,
        public ?string $routeError = null,
    ) {}
}
