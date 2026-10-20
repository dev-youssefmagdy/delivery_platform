<?php

namespace Tests\Feature\Api;

use App\Models\SavedPlace;
use App\Models\ServiceZone;
use App\Models\ZoneServiceCategory;
use Database\Seeders\CurrencySeeder;
use Tests\Concerns\BuildsGeography;
use Tests\Feature\Api\Identity\IdentityTestCase;

/**
 * GET /service-areas/resolve, /places/*, /routes/estimate, zone-filtered /service-categories, /me/saved-places.
 */
class GeographyEndpointsTest extends IdentityTestCase
{
    use BuildsGeography;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    public function test_resolve_inside_and_outside_a_zone(): void
    {
        $category = $this->bookableCategory();
        $zone = $this->zoneOffering([$category], ['restricted_pickup' => true]);

        $this->getJson('/api/v1/service-areas/resolve?lat=30.05&lng=31.25')->assertOk()
            ->assertJsonPath('data.in_service_area', true)
            ->assertJsonPath('data.zone.id', $zone->id)
            ->assertJsonPath('data.zone.restricted_pickup', true)
            ->assertJsonPath('data.city.timezone', 'Africa/Cairo')
            ->assertJsonPath('data.categories.0.service_category_id', $category->id)
            ->assertJsonPath('data.categories.0.available', true)
            ->assertJsonPath('data.categories.0.reason', 'available');

        $this->getJson('/api/v1/service-areas/resolve?lat=40&lng=10')->assertOk()
            ->assertJsonPath('data.in_service_area', false)->assertJsonPath('data.zone', null)->assertJsonPath('data.categories', []);
    }

    public function test_resolve_rejects_impossible_coordinates(): void
    {
        foreach (['', '?lat=30', '?lat=abc&lng=31', '?lat=91&lng=31', '?lat=30&lng=181', '?lat=0&lng=0'] as $query) {
            $this->getJson('/api/v1/service-areas/resolve'.$query)->assertStatus(422)->assertJsonPath('errors._code', 'validation_failed');
        }
    }

    public function test_service_categories_are_filtered_by_the_resolved_zone(): void
    {
        $offered = $this->bookableCategory();
        $notOffered = $this->bookableCategory();
        $closed = $this->bookableCategory(['service_hours' => [['day' => 1, 'from' => '00:00', 'to' => '00:01']]]);
        $zone = $this->zoneOffering([$offered, $closed]);
        ZoneServiceCategory::query()->where('service_category_id', $offered->id)->update(['overrides' => ['min_fare_minor' => 3000]]);
        $this->travelTo(now()->next('Tuesday'));

        $everything = $this->getJson('/api/v1/service-categories')->assertOk();
        $this->assertEqualsCanonicalizing([$offered->id, $notOffered->id, $closed->id], array_column($everything->json('data'), 'id'));
        $this->assertNull($everything->json('data.0.zone_availability'));
        $this->assertArrayNotHasKey('service_area', $everything->json('meta') ?? []);

        $filtered = $this->getJson('/api/v1/service-categories?lat=30.05&lng=31.25')->assertOk();
        $this->assertSame([$offered->id], array_column($filtered->json('data'), 'id'));
        $filtered->assertJsonPath('data.0.zone_availability.zone_id', $zone->id)
            ->assertJsonPath('data.0.zone_availability.overrides.min_fare_minor', 3000)
            ->assertJsonPath('meta.service_area.in_service_area', true)
            ->assertJsonPath('meta.service_area.zone_id', $zone->id);

        $this->getJson('/api/v1/service-categories?lat=40&lng=10')->assertOk()
            ->assertJsonPath('data', [])->assertJsonPath('meta.service_area.in_service_area', false);
        $this->getJson('/api/v1/service-categories?lat=0&lng=0')->assertStatus(422);
    }

    public function test_maps_endpoints_need_a_signed_in_app(): void
    {
        $this->getJson('/api/v1/places/search?q=tahrir')->assertStatus(401);
        $this->getJson('/api/v1/places/reverse?lat=30&lng=31')->assertStatus(401);
        $this->getJson('/api/v1/routes/estimate?origin=30,31&destination=30.1,31.1')->assertStatus(401);
    }

    public function test_place_search_and_reverse(): void
    {
        $token = $this->signIn();

        $this->getJson('/api/v1/places/search?q=tahrir&lat=30.05&lng=31.25', $this->bearer($token))->assertOk()
            ->assertJsonPath('data.0.title', 'Tahrir Square')->assertJsonPath('data.0.precision', 'street')->assertJsonPath('data.0.provider', 'fake');
        $this->getJson('/api/v1/places/search?q=zzzzzz', $this->bearer($token))->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/v1/places/search?q=t', $this->bearer($token))->assertStatus(422)->assertJsonPath('errors._code', 'maps_query_too_short');
        $this->getJson('/api/v1/places/search', $this->bearer($token))->assertStatus(422);
        $this->getJson('/api/v1/places/search?q=abc&lat=30', $this->bearer($token))->assertStatus(422);

        $this->getJson('/api/v1/places/reverse?lat=30.05&lng=31.25', $this->bearer($token))->assertOk()
            ->assertJsonPath('data.precision', 'approximate')->assertJsonPath('data.lat', 30.05);
    }

    public function test_maps_outage_and_missing_configuration_answer_503(): void
    {
        $token = $this->signIn();

        $this->getJson('/api/v1/places/search?q=__unavailable__', $this->bearer($token))
            ->assertStatus(503)->assertJsonPath('errors._code', 'maps_unavailable')->assertJsonPath('success', false);

        config(['integrations.maps.driver' => null]);
        $this->app->forgetInstance(\App\Integrations\Maps\Contracts\MapsProvider::class);
        $this->getJson('/api/v1/places/search?q=tahrir', $this->bearer($token))->assertStatus(503)->assertJsonPath('errors._code', 'maps_unavailable');
        $this->getJson('/api/v1/places/reverse?lat=30.05&lng=31.25', $this->bearer($token))->assertStatus(503);
    }

    public function test_route_estimate(): void
    {
        $category = $this->bookableCategory();
        $this->zoneOffering([$category]);
        $other = $this->bookableCategory();
        $token = $this->signIn();
        $url = '/api/v1/routes/estimate?origin=30.05,31.25&destination=30.06,31.26';

        $this->getJson($url.'&service_category_id='.$category->id, $this->bearer($token))->assertOk()
            ->assertJsonPath('data.provider', 'fake')->assertJsonPath('data.origin_zone_id', ServiceZone::query()->value('id'))
            ->assertJsonStructure(['data' => ['distance_m', 'duration_s', 'polyline']]);
        $this->getJson($url.'&stops[]=30.055,31.255', $this->bearer($token))->assertOk();

        $this->getJson('/api/v1/routes/estimate?origin=40,10&destination=40.1,10.1', $this->bearer($token))
            ->assertStatus(422)->assertJsonPath('errors._code', 'outside_service_area');
        $this->getJson($url.'&service_category_id='.$other->id, $this->bearer($token))
            ->assertStatus(422)->assertJsonPath('errors._code', 'category_unavailable');
        $this->getJson('/api/v1/routes/estimate?origin=30.05,31.25&destination=0.5,0.5', $this->bearer($token))
            ->assertStatus(422)->assertJsonPath('errors._code', 'route_not_found');
        $this->getJson('/api/v1/routes/estimate?origin=bad&destination=30.06,31.26', $this->bearer($token))->assertStatus(422)->assertJsonValidationErrors(['origin']);
        $this->getJson('/api/v1/routes/estimate?origin=0,0&destination=30.06,31.26', $this->bearer($token))->assertStatus(422);
        $this->getJson($url.'&stops[]=nope', $this->bearer($token))->assertStatus(422)->assertJsonValidationErrors(['stops.0']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function place(array $overrides = []): array
    {
        return $overrides + ['label' => 'My home', 'type' => 'home', 'lat' => 30.05, 'lng' => 31.25, 'address' => ['title' => 'Home', 'line' => 'Tahrir, Cairo', 'precision' => 'street', 'provider' => 'fake']];
    }

    public function test_saved_places_crud(): void
    {
        $token = $this->signIn();
        $headers = $this->bearer($token);

        $created = $this->postJson('/api/v1/me/saved-places', $this->place(), $headers)->assertCreated()
            ->assertJsonPath('data.type', 'home')->assertJsonPath('data.lat', 30.05)->assertJsonPath('data.address.precision', 'street');
        $id = $created->json('data.id');

        $this->postJson('/api/v1/me/saved-places', $this->place(['label' => 'Cafe', 'type' => 'other']), $this->bearer($token))->assertCreated();
        $this->postJson('/api/v1/me/saved-places', $this->place(['label' => 'Cafe 2', 'type' => 'other']), $this->bearer($token))->assertCreated();

        $list = $this->getJson('/api/v1/me/saved-places', $this->bearer($token))->assertOk();
        $this->assertSame(['home', 'other', 'other'], array_column($list->json('data'), 'type'));

        $this->putJson("/api/v1/me/saved-places/{$id}", $this->place(['label' => 'Renamed', 'lat' => 30.06]), $this->bearer($token))->assertOk()
            ->assertJsonPath('data.label', 'Renamed')->assertJsonPath('data.lat', 30.06);

        $this->deleteJson("/api/v1/me/saved-places/{$id}", [], $this->bearer($token))->assertOk();
        $this->getJson('/api/v1/me/saved-places', $this->bearer($token))->assertJsonCount(2, 'data');
    }

    public function test_saved_place_rules(): void
    {
        $token = $this->signIn();

        $this->postJson('/api/v1/me/saved-places', $this->place(), $this->bearer($token))->assertCreated();
        $this->postJson('/api/v1/me/saved-places', $this->place(['label' => 'Home 2']), $this->bearer($token))->assertStatus(422)->assertJsonPath('errors._code', 'saved_place_type_taken');
        $this->postJson('/api/v1/me/saved-places', $this->place(['type' => 'work', 'label' => 'Work']), $this->bearer($token))->assertCreated();

        foreach ([['lat' => 0, 'lng' => 0], ['lat' => 95], ['type' => 'vacation'], ['label' => ''], ['address' => ['precision' => 'wild']]] as $bad) {
            $this->postJson('/api/v1/me/saved-places', $this->place($bad + ['type' => 'other']), $this->bearer($token))->assertStatus(422);
        }

        config(['geo.saved_places.max' => 2]);
        $this->postJson('/api/v1/me/saved-places', $this->place(['type' => 'other']), $this->bearer($token))->assertStatus(422)->assertJsonPath('errors._code', 'saved_place_limit_reached');
    }

    public function test_saved_places_are_private_to_their_owner(): void
    {
        $owner = $this->signIn('+201001111111');
        $this->travel(61)->seconds();
        $intruder = $this->signIn('+201002222222');
        $id = $this->postJson('/api/v1/me/saved-places', $this->place(), $this->bearer($owner))->assertCreated()->json('data.id');

        $this->getJson('/api/v1/me/saved-places', $this->bearer($intruder))->assertOk()->assertJsonCount(0, 'data');
        $this->putJson("/api/v1/me/saved-places/{$id}", $this->place(['label' => 'Hijacked']), $this->bearer($intruder))->assertNotFound();
        $this->deleteJson("/api/v1/me/saved-places/{$id}", [], $this->bearer($intruder))->assertNotFound();
        $this->assertSame('My home', SavedPlace::query()->firstOrFail()->label);

        $missing = '00000000-0000-4000-8000-000000000000';
        $this->deleteJson("/api/v1/me/saved-places/{$missing}", [], $this->bearer($owner))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me/saved-places')->assertStatus(401);
    }

    public function test_driver_tokens_cannot_use_saved_places(): void
    {
        $provider = $this->signIn('+201003333333', 'provider');

        $this->getJson('/api/v1/me/saved-places', $this->bearer($provider))->assertStatus(403);
        $this->assertSame(0, SavedPlace::query()->count());
    }
}
