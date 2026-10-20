<?php

namespace Tests\Unit\Integrations;

use App\Enums\Geo\LocationPrecision;
use App\Enums\Geo\MapsProviderStatus;
use App\Integrations\IntegrationGuard;
use App\Integrations\Maps\Contracts\MapsProvider;
use App\Integrations\Maps\Drivers\CachingMapsProvider;
use App\Integrations\Maps\Drivers\FakeMapsProvider;
use App\Integrations\Maps\Drivers\GoogleMapsProvider;
use App\Integrations\Maps\Drivers\OsrmNominatimMapsProvider;
use App\Integrations\Maps\Exceptions\MapsUnavailableException;
use App\Integrations\Maps\Exceptions\NoRouteException;
use App\Support\Geo\GeoPoint;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Real drivers against faked HTTP (no credentials or network here) and the caching decorator.
 */
class MapsProvidersTest extends TestCase
{
    private GeoPoint $a;

    private GeoPoint $b;

    protected function setUp(): void
    {
        parent::setUp();
        config(['maps.osrm_nominatim.min_interval_ms' => 0, 'maps.google.api_key' => 'test-key']);
        $this->a = GeoPoint::make(30.0444, 31.2357);
        $this->b = GeoPoint::make(30.0626, 31.2197);
    }

    private function nominatim(): OsrmNominatimMapsProvider
    {
        return app(OsrmNominatimMapsProvider::class);
    }

    private function google(): GoogleMapsProvider
    {
        return app(GoogleMapsProvider::class);
    }

    public function test_nominatim_search_maps_results_and_precision(): void
    {
        Http::fake(['nominatim.openstreetmap.org/search*' => Http::response([
            ['place_id' => 1, 'lat' => '30.05', 'lon' => '31.25', 'display_name' => '12, Tahrir St, Cairo', 'name' => '', 'addresstype' => 'house', 'place_rank' => 30, 'address' => ['house_number' => '12', 'road' => 'Tahrir St']],
            ['place_id' => 2, 'lat' => '30.06', 'lon' => '31.26', 'display_name' => 'Zamalek, Cairo', 'name' => 'Zamalek', 'addresstype' => 'suburb', 'place_rank' => 20, 'address' => []],
            ['place_id' => 3, 'lat' => 'bad', 'lon' => '31.26', 'display_name' => 'Broken'],
        ])]);

        $places = $this->nominatim()->placesSearch('tahrir', $this->a, 'ar', 5);

        $this->assertCount(2, $places);
        $this->assertSame(LocationPrecision::Exact, $places[0]->precision);
        $this->assertSame('12 Tahrir St', $places[0]->title);
        $this->assertSame(LocationPrecision::Approximate, $places[1]->precision);
        Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', 'DeliveryPlatform/1.0')
            && $request['accept-language'] === 'ar' && $request['countrycodes'] === 'eg' && isset($request['viewbox']));
    }

    public function test_nominatim_reverse_returns_null_when_nothing_is_there(): void
    {
        Http::fake(['nominatim.openstreetmap.org/reverse*' => Http::response(['error' => 'Unable to geocode'])]);

        $this->assertNull($this->nominatim()->reverseGeocode($this->a, 'en'));
    }

    public function test_osrm_route_and_no_route(): void
    {
        Http::fake([
            'router.project-osrm.org/route/v1/driving/31.235700,30.044400;31.219700,30.062600*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 3456.4, 'duration' => 612.2, 'geometry' => '_p~iF~ps|U']]]),
        ]);
        $route = $this->nominatim()->route($this->a, $this->b);
        $this->assertSame(3456, $route->distanceMeters);
        $this->assertSame(612, $route->durationSeconds);
        $this->assertSame('_p~iF~ps|U', $route->polyline);
        $this->assertSame('osrm_nominatim', $route->provider);

        Http::fake(['router.project-osrm.org/*' => Http::response(['code' => 'NoRoute'], 400)]);
        $this->expectException(NoRouteException::class);
        $this->nominatim()->route($this->a, GeoPoint::make(29.97, 31.13));
    }

    public function test_osrm_nominatim_failures_become_maps_unavailable(): void
    {
        foreach ([Http::response('', 500), Http::response('', 429), Http::response('garbage', 200), Http::response(['code' => 'InvalidQuery'], 200)] as $response) {
            Http::fake(['*' => $response]);

            try {
                $this->nominatim()->route($this->a, $this->b);
                $this->fail('Expected MapsUnavailableException');
            } catch (MapsUnavailableException) {
                $this->addToAssertionCount(1);
            }
        }

        Http::fake(['*' => fn () => throw new ConnectionException('timeout')]);
        $this->expectException(MapsUnavailableException::class);
        $this->nominatim()->placesSearch('x y', null, 'ar', 3);
    }

    public function test_osrm_nominatim_without_configuration_is_unavailable(): void
    {
        config(['maps.osrm_nominatim.nominatim_url' => '']);

        $this->assertSame(MapsProviderStatus::NotConfigured, $this->nominatim()->status());
        $this->expectException(MapsUnavailableException::class);
        $this->nominatim()->geocode('x', null, 'ar');
    }

    public function test_google_geocoding_search_and_route_mapping(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => [[
                'formatted_address' => 'Tahrir Square, Cairo', 'place_id' => 'abc', 'types' => ['route'],
                'geometry' => ['location' => ['lat' => 30.04, 'lng' => 31.23], 'location_type' => 'GEOMETRIC_CENTER'],
            ]]]),
            'places.googleapis.com/*' => Http::response(['places' => [[
                'id' => 'p1', 'displayName' => ['text' => 'Cairo Tower'], 'formattedAddress' => 'Gezira, Cairo', 'location' => ['latitude' => 30.045, 'longitude' => 31.224], 'types' => ['tourist_attraction'],
            ]]]),
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 5000, 'duration' => '600s', 'polyline' => ['encodedPolyline' => '_p~iF~ps|U']]]]),
        ]);

        $reverse = $this->google()->reverseGeocode($this->a, 'en');
        $this->assertSame('Tahrir Square', $reverse?->title);
        $this->assertSame(LocationPrecision::Street, $reverse?->precision);

        $found = $this->google()->placesSearch('cairo tower', $this->a, 'en', 5);
        $this->assertSame('Cairo Tower', $found[0]->title);
        $this->assertSame('p1', $found[0]->id);

        $route = $this->google()->route($this->a, $this->b, [GeoPoint::make(30.05, 31.24)]);
        $this->assertSame([5000, 600, '_p~iF~ps|U', 'google'], [$route->distanceMeters, $route->durationSeconds, $route->polyline, $route->provider]);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'routes.googleapis.com') && $request->hasHeader('X-Goog-Api-Key', 'test-key') && isset($request['intermediates']));
    }

    public function test_google_errors_quota_and_missing_key_are_unavailable_and_never_leak_the_key(): void
    {
        foreach ([
            ['maps.googleapis.com/*', Http::response(['status' => 'REQUEST_DENIED'], 200)],
            ['maps.googleapis.com/*', Http::response(['status' => 'OVER_QUERY_LIMIT'], 200)],
            ['maps.googleapis.com/*', Http::response('', 503)],
        ] as [$pattern, $response]) {
            Http::fake([$pattern => $response]);

            try {
                $this->google()->geocode('x y', null, 'ar');
                $this->fail('Expected MapsUnavailableException');
            } catch (MapsUnavailableException $e) {
                $this->assertStringNotContainsString('test-key', $e->getMessage());
                $this->assertNull($e->getPrevious());
            }
        }

        Http::fake(['routes.googleapis.com/*' => Http::response(['routes' => []])]);
        try {
            $this->google()->route($this->a, $this->b);
            $this->fail('Expected NoRouteException');
        } catch (NoRouteException) {
            $this->addToAssertionCount(1);
        }

        config(['maps.google.api_key' => '']);
        $this->assertSame(MapsProviderStatus::NotConfigured, $this->google()->status());
        $this->expectException(MapsUnavailableException::class);
        $this->google()->placesSearch('x y', null, 'ar', 3);
    }

    public function test_caching_decorator_caches_successes_and_never_failures(): void
    {
        Cache::flush();
        $calls = 0;
        $inner = new class(function () use (&$calls): void {
            $calls++;
        }) implements MapsProvider
        {

            public bool $fail = false;

            public function __construct(private readonly Closure $tick) {}

            public function name(): string
            {
                return 'counting';
            }

            public function status(): MapsProviderStatus
            {
                return MapsProviderStatus::Ok;
            }

            public function geocode(string $query, ?GeoPoint $near, string $locale): array
            {
                return (new FakeMapsProvider)->geocode($query, $near, $locale);
            }

            public function reverseGeocode(GeoPoint $point, string $locale): ?\App\DTOs\Geography\PlaceData
            {
                ($this->tick)();

                return $this->fail ? throw MapsUnavailableException::because('down') : (new FakeMapsProvider)->reverseGeocode($point, $locale);
            }

            public function placesSearch(string $query, ?GeoPoint $near, string $locale, int $limit): array
            {
                ($this->tick)();

                return (new FakeMapsProvider)->placesSearch($query, $near, $locale, $limit);
            }

            public function route(GeoPoint $origin, GeoPoint $destination, array $stops = []): \App\DTOs\Geography\RouteData
            {
                ($this->tick)();

                return (new FakeMapsProvider)->route($origin, $destination, $stops);
            }
        };
        $cached = new CachingMapsProvider($inner, Cache::store());

        $cached->placesSearch('Tahrir', null, 'en', 5);
        $cached->placesSearch('  tahrir ', null, 'en', 5);
        $this->assertSame(1, $calls);
        $cached->placesSearch('tahrir', null, 'ar', 5);
        $this->assertSame(2, $calls);

        $cached->route($this->a, $this->b);
        $cached->route($this->a, $this->b);
        $this->assertSame(3, $calls);

        $inner->fail = true;
        for ($i = 0; $i < 2; $i++) {
            try {
                $cached->reverseGeocode($this->a, 'en');
            } catch (MapsUnavailableException) {
            }
        }
        $this->assertSame(5, $calls, 'failures must not be cached');

        $inner->fail = false;
        $this->assertNotNull($cached->reverseGeocode($this->a, 'en'));
        $cached->reverseGeocode($this->a, 'en');
        $this->assertSame(6, $calls);
    }

    public function test_fake_driver_is_refused_outside_local_and_testing(): void
    {
        $this->expectException(RuntimeException::class);
        IntegrationGuard::assertAllowed(['maps' => 'fake'], 'production', ['fake', 'log'], ['local', 'testing']);
    }

    public function test_fake_driver_behaviour(): void
    {
        $fake = new FakeMapsProvider;

        $this->assertSame('Tahrir Square', $fake->placesSearch('tahrir', null, 'en', 5)[0]->title);
        $this->assertSame([], $fake->placesSearch('nothing-here', null, 'en', 5));
        $route = $fake->route($this->a, $this->b);
        $this->assertGreaterThan(1000, $route->distanceMeters);
        $this->expectException(NoRouteException::class);
        $fake->route($this->a, GeoPoint::make(FakeMapsProvider::NO_ROUTE_LAT, 0.5));
    }
}
