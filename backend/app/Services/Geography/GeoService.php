<?php

namespace App\Services\Geography;

use App\DTOs\Geography\CategoryAvailability;
use App\DTOs\Geography\ResolvedArea;
use App\Enums\Geo\AvailabilityReason;
use App\Models\ServiceCategory;
use App\Models\ZoneServiceCategory;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Repositories\Contracts\Geography\ZoneServiceCategoryRepositoryInterface;
use App\Support\Geo\Distance;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\OperatingHours;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Geography reads: which zone serves a position and which categories it offers right now.
 *
 * A category is available at a position when (a) a zone that is active, in an active city, contains it
 * (ST_Contains), (b) the zone has the category enabled, (c) the category is active and published, and (d) the
 * moment is inside all three opening-hours layers: the zone's operating hours, the category's own service
 * hours and the zone's per-category hours override, evaluated in the city's timezone. Overlapping zones
 * resolve to the smallest one.
 */
final class GeoService
{
    public function __construct(
        private readonly ServiceZoneRepositoryInterface $zones,
        private readonly ZoneServiceCategoryRepositoryInterface $links,
    ) {}

    public function resolve(GeoPoint $point, ?CarbonInterface $at = null): ResolvedArea
    {
        $zones = $this->zones->containing($point);
        $zone = $zones->first();

        if ($zone === null) {
            return new ResolvedArea($point, null, null, $zones, [], null);
        }

        $city = $zone->city;
        $local = CarbonImmutable::instance($at ?? now())->setTimezone($city->timezone);
        $zoneOpen = $zone->hours()->isOpenAt($local);
        $categories = [];

        foreach ($this->links->forZone($zone->id) as $link) {
            $category = $link->category;

            if (! $link->active || ! $category->active || $category->published_version_id === null) {
                continue;
            }

            $categories[] = $this->availability($zone->hours(), $zoneOpen, $category, $link, $local);
        }

        usort($categories, static fn (CategoryAvailability $a, CategoryAvailability $b): int => [$a->category->sort_order, $a->category->id] <=> [$b->category->sort_order, $b->category->id]);

        return new ResolvedArea($point, $city, $zone, $zones, $categories, $local);
    }

    /**
     * Haversine distance for ranking and sanity checks (zone membership always uses MySQL spatial functions).
     */
    public function distanceMeters(GeoPoint $a, GeoPoint $b): float
    {
        return Distance::haversineMeters($a, $b);
    }

    /**
     * @return Collection<int, \App\Models\ServiceZone>
     */
    public function zonesAt(GeoPoint $point): Collection
    {
        return $this->zones->containing($point);
    }

    private function availability(OperatingHours $zoneHours, bool $zoneOpen, ServiceCategory $category, ZoneServiceCategory $link, CarbonInterface $local): CategoryAvailability
    {
        /** @var array<string, mixed> $overrides */
        $overrides = $link->overrides ?? [];
        $categoryHours = OperatingHours::fromArray($category->service_hours);
        $window = isset($overrides['hours']['from'], $overrides['hours']['to']) ? $overrides['hours'] : null;
        $time = $local->format('H:i');

        $reason = match (true) {
            ! $zoneOpen => AvailabilityReason::ZoneClosed,
            ! $categoryHours->isOpenAt($local) => AvailabilityReason::CategoryClosed,
            $window !== null && ! ($window['from'] <= $time && $time < $window['to']) => AvailabilityReason::OverrideClosed,
            default => AvailabilityReason::Available,
        };

        $opensAt = match ($reason) {
            AvailabilityReason::ZoneClosed => $zoneHours->nextOpeningAfter($local),
            AvailabilityReason::CategoryClosed => $categoryHours->nextOpeningAfter($local),
            default => null,
        };

        return new CategoryAvailability($category, $reason, $overrides, $opensAt);
    }
}
