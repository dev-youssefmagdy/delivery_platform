<?php

namespace Tests\Feature\Geography;

use App\Enums\Geo\AvailabilityReason;
use App\Models\City;
use App\Models\ServiceZone;
use App\Models\ZoneServiceCategory;
use App\Services\Geography\GeoService;
use App\Support\Geo\GeoPoint;
use App\Support\Geo\GeoPolygon;
use Carbon\CarbonImmutable;
use Database\Factories\ServiceZoneFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGeography;
use Tests\TestCase;

/**
 * Point-in-zone, zone override resolution and operating hours (Plan P1.5 tests).
 */
class GeoServiceTest extends TestCase
{
    use BuildsGeography;
    use RefreshDatabase;

    private function geo(): GeoService
    {
        return app(GeoService::class);
    }

    private function inside(): GeoPoint
    {
        return GeoPoint::make(30.05, 31.25);
    }

    public function test_polygon_and_point_survive_a_database_round_trip_in_lat_lng_order(): void
    {
        $zone = ServiceZone::factory()->create();
        $city = City::factory()->create();

        $fresh = ServiceZone::query()->findOrFail($zone->id);
        $this->assertSame($zone->polygon->toArray(), $fresh->polygon->toArray());
        $this->assertEqualsWithDelta(30.0, $fresh->polygon->vertices()[0]->lat, 1e-6);
        $this->assertEqualsWithDelta(31.2, $fresh->polygon->vertices()[0]->lng, 1e-6);
        $this->assertEqualsWithDelta(31.2357, City::query()->findOrFail($city->id)->center->lng, 1e-6);
    }

    public function test_point_in_polygon_for_both_ring_orientations_and_outside_points(): void
    {
        $category = $this->bookableCategory();
        $clockwise = GeoPolygon::fromLatLngList([[30.0, 31.2], [30.1, 31.2], [30.1, 31.3], [30.0, 31.3]]);
        $this->zoneOffering([$category], ['polygon' => $clockwise]);

        $this->assertTrue($this->geo()->resolve($this->inside())->inServiceArea());
        $this->assertFalse($this->geo()->resolve(GeoPoint::make(30.2, 31.25))->inServiceArea());
        $this->assertFalse($this->geo()->resolve(GeoPoint::make(30.05, 31.35))->inServiceArea());
        $this->assertFalse($this->geo()->resolve(GeoPoint::make(-30.05, -31.25))->inServiceArea());
    }

    public function test_only_active_zones_of_active_cities_serve(): void
    {
        $category = $this->bookableCategory();
        $this->zoneOffering([$category], ['status' => 'draft']);
        $this->zoneOffering([$category], ['status' => 'suspended']);
        $this->zoneOffering([$category], [], City::factory()->inactive()->create());

        $this->assertFalse($this->geo()->resolve($this->inside())->inServiceArea());

        $this->zoneOffering([$category]);
        $this->assertTrue($this->geo()->resolve($this->inside())->inServiceArea());
    }

    public function test_overlapping_zones_resolve_to_the_smallest(): void
    {
        $category = $this->bookableCategory();
        $big = $this->zoneOffering([$category]);
        $small = $this->zoneOffering([$category], ['polygon' => ServiceZoneFactory::square(30.04, 31.24, 0.02)]);

        $area = $this->geo()->resolve($this->inside());

        $this->assertSame($small->id, $area->zone?->id);
        $this->assertSame([$small->id, $big->id], $area->zones->pluck('id')->all());
        $this->assertSame($big->id, $this->geo()->resolve(GeoPoint::make(30.09, 31.29))->zone?->id);
    }

    public function test_only_enabled_active_published_categories_are_listed_with_overrides(): void
    {
        $enabled = $this->bookableCategory();
        $disabled = $this->bookableCategory();
        $unpublished = \App\Models\ServiceCategory::factory()->active()->create();
        $inactive = $this->bookableCategory();
        $inactive->forceFill(['active' => false])->save();
        $zone = $this->zoneOffering([$enabled, $inactive]);
        ZoneServiceCategory::query()->create(['service_zone_id' => $zone->id, 'service_category_id' => $disabled->id, 'active' => false]);
        ZoneServiceCategory::query()->create(['service_zone_id' => $zone->id, 'service_category_id' => $unpublished->id, 'active' => true]);
        ZoneServiceCategory::query()->where('service_category_id', $enabled->id)->update(['overrides' => ['min_fare_minor' => 2500]]);

        $area = $this->geo()->resolve($this->inside());

        $this->assertSame([$enabled->id], array_map(fn ($c) => $c->category->id, $area->categories));
        $this->assertSame(['min_fare_minor' => 2500], $area->availability($enabled->id)?->overrides);
        $this->assertNull($area->availability($disabled->id));
    }

    public function test_zone_hours_are_evaluated_in_the_city_timezone(): void
    {
        $category = $this->bookableCategory();
        $zone = $this->zoneOffering([$category], ['operating_hours' => [['day' => 1, 'from' => '06:00', 'to' => '23:00']]]);

        // Monday 2026-10-19 10:00 UTC is 13:00 in Cairo (EEST, UTC+3): open. 03:00 UTC is 06:00 local: open; 02:59 UTC: closed.
        $open = $this->geo()->resolve($this->inside(), CarbonImmutable::parse('2026-10-19 10:00', 'UTC'));
        $this->assertTrue($open->availability($category->id)?->isAvailable());

        $edge = $this->geo()->resolve($this->inside(), CarbonImmutable::parse('2026-10-19 03:00', 'UTC'));
        $this->assertTrue($edge->availability($category->id)?->isAvailable());

        $closed = $this->geo()->resolve($this->inside(), CarbonImmutable::parse('2026-10-19 02:59', 'UTC'));
        $availability = $closed->availability($category->id);
        $this->assertSame(AvailabilityReason::ZoneClosed, $availability?->reason);
        $this->assertSame('2026-10-19 06:00', $availability?->opensAt?->format('Y-m-d H:i'));
        $this->assertSame([], $closed->availableCategories());

        $tuesday = $this->geo()->resolve($this->inside(), CarbonImmutable::parse('2026-10-20 10:00', 'UTC'));
        $this->assertSame(AvailabilityReason::ZoneClosed, $tuesday->availability($category->id)?->reason);
        $this->assertSame($zone->id, $tuesday->zone?->id);
    }

    public function test_category_hours_and_zone_override_hours_both_apply(): void
    {
        $byCategory = $this->bookableCategory(['service_hours' => [['day' => 1, 'from' => '09:00', 'to' => '17:00']]]);
        $byOverride = $this->bookableCategory();
        $zone = $this->zoneOffering([$byCategory, $byOverride]);
        ZoneServiceCategory::query()->where('service_zone_id', $zone->id)->where('service_category_id', $byOverride->id)->update(['overrides' => ['hours' => ['from' => '10:00', 'to' => '12:00']]]);

        $at = static fn (string $local): CarbonImmutable => CarbonImmutable::parse("2026-10-19 {$local}", 'Africa/Cairo');

        $noon = $this->geo()->resolve($this->inside(), $at('11:00'));
        $this->assertTrue($noon->availability($byCategory->id)?->isAvailable());
        $this->assertTrue($noon->availability($byOverride->id)?->isAvailable());

        $afternoon = $this->geo()->resolve($this->inside(), $at('15:00'));
        $this->assertTrue($afternoon->availability($byCategory->id)?->isAvailable());
        $this->assertSame(AvailabilityReason::OverrideClosed, $afternoon->availability($byOverride->id)?->reason);

        $evening = $this->geo()->resolve($this->inside(), $at('18:00'));
        $this->assertSame(AvailabilityReason::CategoryClosed, $evening->availability($byCategory->id)?->reason);
        $this->assertSame('2026-10-26 09:00', $evening->availability($byCategory->id)?->opensAt?->format('Y-m-d H:i'));
    }
}
