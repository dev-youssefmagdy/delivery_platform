<?php

namespace App\Services\Geography;

use App\DTOs\Geography\RouteEstimate;
use App\Exceptions\BusinessRuleViolation;
use App\Services\Pricing\PriceGuidanceService;
use App\Support\Geo\GeoPoint;

/**
 * Route estimates for customers: the pickup must be inside an active zone (and, when a category is given,
 * that category must be bookable there right now) before any paid maps call is made.
 */
final class RouteEstimateService
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly MapsService $maps,
        private readonly PriceGuidanceService $pricing,
    ) {}

    /**
     * @param list<GeoPoint> $stops
     * @param array<string, mixed> $serviceDetails answers that may carry surcharges (guidance only, not validated here)
     */
    public function estimate(GeoPoint $origin, GeoPoint $destination, array $stops = [], ?int $serviceCategoryId = null, array $serviceDetails = []): RouteEstimate
    {
        $area = $this->geo->resolve($origin);

        if (! $area->inServiceArea()) {
            throw BusinessRuleViolation::make('outside_service_area', 'origin');
        }

        $availability = $serviceCategoryId === null ? null : $area->availability($serviceCategoryId);

        if ($serviceCategoryId !== null && $availability?->isAvailable() !== true) {
            throw BusinessRuleViolation::make('category_unavailable', 'service_category_id');
        }

        $route = $this->maps->route($origin, $destination, $stops);
        $pricing = $availability === null
            ? null
            : $this->pricing->guide($availability->category, $area->zone?->id, $route, count($stops), $serviceDetails, now());

        return new RouteEstimate($route, $area, $pricing);
    }
}
