<?php

namespace Tests\Feature\Location;

use App\DTOs\Location\ProviderPosition;
use App\Models\City;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Services\Location\Index\DatabaseGeoIndex;
use App\Services\Location\Index\ProviderGeoIndex;
use App\Services\Location\Index\RedisGeoIndex;
use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsDispatch;
use Tests\TestCase;
use Throwable;

/**
 * Both ProviderGeoIndex implementations answer the same questions the same way: radius, nearest first, freshness,
 * city separation, removal and moving between cities. The Redis run needs a local Redis (database 15) and is skipped
 * when none answers.
 */
class ProviderGeoIndexTest extends TestCase
{
    use BuildsDispatch;
    use RefreshDatabase;

    private const CENTRE_LAT = 30.05;

    private const CENTRE_LNG = 31.25;

    /**
     * @return array<string, array{string}>
     */
    public static function implementations(): array
    {
        return ['database' => ['database'], 'redis' => ['redis']];
    }

    private function index(string $driver): ProviderGeoIndex
    {
        if ($driver === 'redis') {
            try {
                app('redis')->connection()->ping();
            } catch (Throwable) {
                $this->markTestSkipped('Redis is not available.');
            }

            $index = app(RedisGeoIndex::class);
            $index->flush();

            return $index;
        }

        return app(DatabaseGeoIndex::class);
    }

    protected function tearDown(): void
    {
        try {
            if (config('location.index') === 'redis' || app()->bound(RedisGeoIndex::class)) {
                app(RedisGeoIndex::class)->flush();
            }
        } catch (Throwable) {
        }

        parent::tearDown();
    }

    /**
     * Places an online provider the way the implementation expects it.
     */
    private function place(ProviderGeoIndex $index, City $city, float $lat, float $lng, ?CarbonImmutable $at = null): ProviderProfile
    {
        $provider = $this->makeProvider();
        $provider->forceFill(['is_online' => true])->save();
        $at ??= CarbonImmutable::now();
        $sample = $this->sampleAt($lat, $lng, $at);
        $stored = app(ProviderLastLocationRepositoryInterface::class)->upsert($provider, $sample, $city->id, now());
        $index->put(new ProviderPosition($provider->id, $city->id, $sample->point, $at, 8.0, 90, 3.5, [7], [9]), 90);

        return $provider;
    }

    /**
     * @return list<int>
     */
    private function ids(array $hits): array
    {
        return array_map(static fn ($hit): int => $hit->position->providerId, $hits);
    }

    #[DataProvider('implementations')]
    public function test_it_finds_providers_within_the_radius_nearest_first(string $driver): void
    {
        $index = $this->index($driver);
        $city = City::factory()->create();
        $near = $this->place($index, $city, 30.051, 31.251);   // ~140 m
        $mid = $this->place($index, $city, 30.07, 31.25);      // ~2.2 km
        $far = $this->place($index, $city, 30.10, 31.25);      // ~5.5 km

        $centre = GeoPoint::make(self::CENTRE_LAT, self::CENTRE_LNG);
        $fresh = CarbonImmutable::now()->subMinute();

        $within3 = $index->nearby($city->id, $centre, 3000, $fresh, 10);
        $this->assertSame([$near->id, $mid->id], $this->ids($within3));
        $this->assertEqualsWithDelta(140, $within3[0]->distanceMeters, 15);
        $this->assertEqualsWithDelta(2224, $within3[1]->distanceMeters, 30);

        $this->assertSame([$near->id, $mid->id, $far->id], $this->ids($index->nearby($city->id, $centre, 7000, $fresh, 10)));
        $this->assertSame([$near->id], $this->ids($index->nearby($city->id, $centre, 7000, $fresh, 1)));
        $this->assertSame([], $index->nearby($city->id, $centre, 50, $fresh, 10));
    }

    #[DataProvider('implementations')]
    public function test_stale_positions_are_not_returned(string $driver): void
    {
        $index = $this->index($driver);
        $city = City::factory()->create();
        $this->place($index, $city, 30.051, 31.251, CarbonImmutable::now()->subMinutes(10));
        $fresh = $this->place($index, $city, 30.052, 31.251);

        $hits = $index->nearby($city->id, GeoPoint::make(self::CENTRE_LAT, self::CENTRE_LNG), 3000, CarbonImmutable::now()->subSeconds(90), 10);

        $this->assertSame([$fresh->id], $this->ids($hits));
    }

    #[DataProvider('implementations')]
    public function test_providers_of_another_city_are_not_returned(string $driver): void
    {
        $index = $this->index($driver);
        $cairo = City::factory()->create();
        $other = City::factory()->create();
        $mine = $this->place($index, $cairo, 30.051, 31.251);
        $this->place($index, $other, 30.052, 31.251);

        $hits = $index->nearby($cairo->id, GeoPoint::make(self::CENTRE_LAT, self::CENTRE_LNG), 3000, CarbonImmutable::now()->subMinute(), 10);

        $this->assertSame([$mine->id], $this->ids($hits));
    }

    #[DataProvider('implementations')]
    public function test_a_removed_provider_is_not_returned_and_get_reflects_it(string $driver): void
    {
        $index = $this->index($driver);
        $city = City::factory()->create();
        $provider = $this->place($index, $city, 30.051, 31.251);

        $this->assertNotNull($index->get($provider->id));
        $this->assertEqualsWithDelta(30.051, $index->get($provider->id)->point->lat, 1e-5);

        app(ProviderProfileRepositoryInterface::class)->setOnline($provider, false);
        $index->remove($provider->id);

        $this->assertNull($index->get($provider->id));
        $this->assertSame([], $index->nearby($city->id, GeoPoint::make(self::CENTRE_LAT, self::CENTRE_LNG), 3000, CarbonImmutable::now()->subMinute(), 10));
    }

    public function test_redis_keeps_the_category_lists_and_ttl_freshness_and_moves_between_cities(): void
    {
        $index = $this->index('redis');
        $a = City::factory()->create();
        $b = City::factory()->create();
        $centre = GeoPoint::make(self::CENTRE_LAT, self::CENTRE_LNG);
        $now = CarbonImmutable::now();

        $index->put(new ProviderPosition(41, $a->id, GeoPoint::make(30.051, 31.251), $now, 5.0, 10, 2.0, [3, 4], [8]), 90);
        $position = $index->get(41);

        $this->assertSame([3, 4], $position->vehicleCategoryIds);
        $this->assertSame([8], $position->serviceCategoryIds);
        $this->assertSame($a->id, $position->cityId);
        $this->assertSame(10, $position->heading);

        $index->put(new ProviderPosition(41, $b->id, GeoPoint::make(30.051, 31.251), $now, null, null, null), 90);
        $this->assertSame([], $index->nearby($a->id, $centre, 3000, $now->subMinute(), 10));
        $this->assertSame([41], $this->ids($index->nearby($b->id, $centre, 3000, $now->subMinute(), 10)));

        // A hash that expired takes the provider out of the GEO set the next time a search meets it.
        $index->put(new ProviderPosition(42, $b->id, GeoPoint::make(30.052, 31.251), $now, null, null, null), 1);
        usleep(2_200_000);
        $this->assertSame([41], $this->ids($index->nearby($b->id, $centre, 3000, $now->subMinute(), 10)));
        $this->assertNull($index->get(42));
    }

    public function test_the_configured_driver_selects_the_implementation(): void
    {
        config(['location.index' => 'database']);
        $this->assertInstanceOf(DatabaseGeoIndex::class, app(ProviderGeoIndex::class));

        config(['location.index' => 'redis']);
        $this->assertInstanceOf(RedisGeoIndex::class, app(ProviderGeoIndex::class));
    }
}
