<?php

namespace Tests\Feature\Geography;

use App\Enums\Geo\ZoneStatus;
use App\Models\City;
use App\Models\ServiceZone;
use App\Services\Geography\GeoService;
use App\Support\Geo\GeoPoint;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_labelled_demo_zones_idempotently_and_they_resolve(): void
    {
        config(['geo.seed_demo' => true]);
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CatalogSeeder::class]);

        $this->seed(GeographySeeder::class);
        $this->seed(GeographySeeder::class);

        $this->assertSame(2, City::query()->count());
        $this->assertSame(2, ServiceZone::query()->count());
        $zone = ServiceZone::query()->where('name->en', 'Central Cairo (demo)')->firstOrFail();
        $this->assertTrue($zone->is_demo);
        $this->assertSame(ZoneStatus::Active, $zone->status);
        $this->assertStringContainsString('DEMO DATA', (string) $zone->notes);

        $area = app(GeoService::class)->resolve(GeoPoint::make(30.0444, 31.2357));
        $this->assertSame($zone->id, $area->zone?->id);
        $this->assertCount(8, $area->categories);
    }

    public function test_nothing_is_seeded_when_demo_data_is_off(): void
    {
        config(['geo.seed_demo' => false]);
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CatalogSeeder::class]);

        $this->seed(GeographySeeder::class);

        $this->assertSame(0, City::query()->count());
    }
}
