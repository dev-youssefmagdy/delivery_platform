<?php

namespace App\Services\Geography;

use App\DTOs\Geography\RouteEstimate;
use App\Exceptions\BusinessRuleViolation;
use App\Support\Geo\GeoPoint;

/**
 * Route estimates for customers: the pickup must be inside an active zone (and, when a category is given,
 * that category must be bookable there right now) before any paid maps call is made.
 */
final class RouteEstimateService
{
    public function __construct(private readonly GeoService $geo, private readonly MapsService $maps) {}

    /**
     * @param list<GeoPoint> $stops
     */
    public function estimate(GeoPoint $origin, GeoPoint $destination, array $stops = [], ?int $serviceCategoryId = null): RouteEstimate
    {
        $area = $this->geo->resolve($origin);

        if (! $area->inServiceArea()) {
            throw BusinessRuleViolation::make('outside_service_area', 'origin');
        }

        if ($serviceCategoryId !== null && ($area->availability($serviceCategoryId)?->isAvailable() !== true)) {
            throw BusinessRuleViolation::make('category_unavailable', 'service_category_id');
        }

        return new RouteEstimate($this->maps->route($origin, $destination, $stops), $area);
    }
}
