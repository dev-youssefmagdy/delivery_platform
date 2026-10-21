<?php

namespace Tests\Feature\Provider;

use App\Enums\Location\OfflineReason;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\VehicleStatus;
use App\Events\Realtime\ProviderAvailabilityChanged;
use App\Models\ProviderLastLocation;
use App\Services\Location\Index\ProviderGeoIndex;
use App\Services\Provider\AvailabilityService;
use App\Services\Provider\ProviderStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsDispatch;
use Tests\TestCase;

/**
 * Online / offline through the API: eligibility reasons as a 422, vehicle choice, the confirmed offline workflow,
 * the stale-location sweep and automatic offline on suspension or document expiry.
 */
class AvailabilityApiTest extends TestCase
{
    use BuildsDispatch;
    use RefreshDatabase;

    private function goOnline(array $extra = [], ?array $location = null)
    {
        return $this->postJson(route('api.v1.provider.availability.online'), $extra + ['location' => $location ?? $this->samplePayload()]);
    }

    public function test_an_eligible_provider_goes_online_with_tracking_and_channel_info(): void
    {
        Event::fake([ProviderAvailabilityChanged::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $vehicle = $provider->vehicles()->firstOrFail();
        $this->actingAsProvider($provider);

        $this->goOnline()
            ->assertOk()
            ->assertJsonPath('data.online', true)
            ->assertJsonPath('data.vehicle_id', $vehicle->uuid)
            ->assertJsonPath('data.tracking.state', 'idle')
            ->assertJsonPath('data.tracking.interval_seconds', 30)
            ->assertJsonPath('data.realtime.channel', 'private-provider.'.$provider->uuid);

        $provider->refresh();
        $this->assertTrue($provider->is_online);
        $this->assertSame($vehicle->id, $provider->current_vehicle_id);
        $this->assertNotNull($provider->online_since);
        $this->assertSame($city->id, ProviderLastLocation::query()->firstOrFail()->city_id);
        $this->assertNotNull(app(ProviderGeoIndex::class)->get($provider->id));
        Event::assertDispatched(ProviderAvailabilityChanged::class, static fn ($event): bool => $event->payload['online'] === true
            && $event->broadcastOn()[0]->name === 'private-provider.'.$provider->uuid);
    }

    public function test_going_online_twice_is_harmless(): void
    {
        Event::fake([ProviderAvailabilityChanged::class]);
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);

        $this->goOnline()->assertOk();
        $this->goOnline(location: $this->samplePayload(30.0501, 31.2501, CarbonImmutable::now()->addSeconds(5)))->assertOk()->assertJsonPath('data.online', true);

        Event::assertDispatchedTimes(ProviderAvailabilityChanged::class, 1);
    }

    public function test_an_ineligible_provider_gets_a_422_with_typed_reasons_and_stays_offline(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->makeProvider(ProviderStatus::UnderReview);
        $this->actingAsProvider($provider);

        $response = $this->goOnline()->assertStatus(422)->assertJsonPath('errors._code', 'provider_not_eligible');
        $reasons = array_column($response->json('meta.reasons'), 'reason');

        $this->assertContains('not_approved', $reasons);
        $this->assertContains('no_approved_vehicle', $reasons);
        $this->assertFalse($provider->refresh()->is_online);
        $this->assertSame(0, ProviderLastLocation::query()->count());
    }

    public function test_a_provider_without_an_application_cannot_go_online(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAsProvider($user);

        $this->goOnline()->assertStatus(422)->assertJsonPath('meta.reasons.0.reason', 'not_approved');
    }

    public function test_the_busy_and_in_debt_reasons_block_going_online(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);

        $this->fakeWorkload($this->busy());
        $this->assertContains('active_job_exists', array_column($this->goOnline()->assertStatus(422)->json('meta.reasons'), 'reason'));

    }

    public function test_cash_debt_over_the_limit_blocks_going_online(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);
        $this->fakeWorkload(null, 999_999_999);

        $this->assertContains('cash_debt_limit_exceeded', array_column($this->goOnline()->assertStatus(422)->json('meta.reasons'), 'reason'));
    }

    public function test_a_stale_position_blocks_going_online(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);

        $this->goOnline(location: $this->samplePayload(30.05, 31.25, CarbonImmutable::now()->subMinutes(5)))
            ->assertStatus(422);
    }

    public function test_a_position_outside_every_service_zone_is_refused(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);

        $this->goOnline(location: $this->samplePayload(31.2, 29.9))
            ->assertStatus(422)->assertJsonPath('errors._code', 'outside_service_area');
        $this->assertFalse($provider->refresh()->is_online);
    }

    public function test_the_vehicle_can_be_chosen_among_usable_vehicles_only(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $second = $this->vehicleFor($provider, VehicleStatus::Approved, ['vehicle_category_id' => $category->vehicleCategories()->firstOrFail()->id]);
        $draft = $this->vehicleFor($provider, VehicleStatus::Draft);
        $stranger = $this->vehicleFor($this->approvedProviderFor($category), VehicleStatus::Approved);
        $this->actingAsProvider($provider);

        $this->goOnline(['vehicle_id' => $draft->uuid])->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_not_usable');
        $this->goOnline(['vehicle_id' => $stranger->uuid])->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_not_usable');
        $this->goOnline(['vehicle_id' => $second->uuid])->assertOk()->assertJsonPath('data.vehicle_id', $second->uuid);
    }

    public function test_the_state_endpoint_shows_availability_and_working_eligibility(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);

        $this->getJson(route('api.v1.provider.availability.show'))
            ->assertOk()
            ->assertJsonPath('data.availability.online', false)
            ->assertJsonPath('data.availability.tracking.state', 'off')
            ->assertJsonPath('data.eligibility.eligible', false)
            ->assertJsonPath('data.eligibility.reasons.0.reason', 'location_stale');
    }

    public function test_going_offline_clears_the_index_and_stops_tracking(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);

        $this->postJson(route('api.v1.provider.availability.offline'))
            ->assertOk()
            ->assertJsonPath('data.online', false)
            ->assertJsonPath('data.tracking.state', 'off')
            ->assertJsonPath('data.last_offline_reason', 'provider_choice');

        $this->assertFalse($provider->refresh()->is_online);
        $this->assertNull(app(ProviderGeoIndex::class)->get($provider->id));
        $this->postJson(route('api.v1.provider.availability.offline'))->assertOk();
    }

    public function test_offline_with_an_active_job_needs_confirmation_and_the_job_stays_tracked(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->fakeWorkload($this->busy());
        $this->actingAsProvider($provider);

        $this->postJson(route('api.v1.provider.availability.offline'))
            ->assertStatus(409)->assertJsonPath('errors._code', 'active_job_blocks_offline');
        $this->assertTrue($provider->refresh()->is_online);

        $this->postJson(route('api.v1.provider.availability.offline'), ['confirm' => true])
            ->assertOk()->assertJsonPath('data.online', false)->assertJsonPath('data.tracking.state', 'active');
        $this->assertFalse($provider->refresh()->is_online);
    }

    public function test_the_sweep_takes_providers_with_a_stale_position_offline(): void
    {
        ['category' => $category] = $this->cairo();
        $stale = $this->onlineProviderFor($category);
        $fresh = $this->onlineProviderFor($category, 30.052);
        ProviderLastLocation::query()->where('provider_profile_id', $stale->id)->update(['recorded_at' => now()->subMinutes(5)]);
        $stale->forceFill(['online_since' => now()->subMinutes(6)])->save();

        $this->assertSame(1, app(AvailabilityService::class)->sweepStale());

        $this->assertFalse($stale->refresh()->is_online);
        $this->assertSame(OfflineReason::StaleLocation, $stale->last_offline_reason);
        $this->assertNull(app(ProviderGeoIndex::class)->get($stale->id));
        $this->assertTrue($fresh->refresh()->is_online);
        $this->assertSame(0, app(AvailabilityService::class)->sweepStale());
    }

    public function test_the_sweep_catches_an_online_provider_that_never_reported_a_position(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $provider->forceFill(['is_online' => true, 'online_since' => now()->subMinutes(10)])->save();

        $this->assertSame(1, app(AvailabilityService::class)->sweepStale());
        $this->assertFalse($provider->refresh()->is_online);
    }

    public function test_suspending_a_provider_takes_it_offline_immediately(): void
    {
        Event::fake([ProviderAvailabilityChanged::class]);
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);

        app(ProviderStatusService::class)->transition($provider, ProviderStatus::TemporarilySuspended, null, 'test', now()->addDay());

        $this->assertFalse($provider->refresh()->is_online);
        $this->assertSame(OfflineReason::AccountChanged, $provider->last_offline_reason);
        $this->assertNull(app(ProviderGeoIndex::class)->get($provider->id));
        Event::assertDispatched(ProviderAvailabilityChanged::class, static fn ($event): bool => $event->payload['online'] === false && $event->payload['reason'] === 'account_changed');
    }

    public function test_availability_endpoints_are_for_provider_tokens_and_active_accounts(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);

        $this->postJson(route('api.v1.provider.availability.online'), ['location' => $this->samplePayload()])->assertUnauthorized();

        $this->actingAsProvider($provider, 'customer');
        $this->goOnline()->assertForbidden();

        $user = $this->actingAsProvider($provider);
        $user->forceFill(['status' => \App\Enums\Identity\UserStatus::Suspended])->save();
        $this->goOnline()->assertForbidden();
    }
}
