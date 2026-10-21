<?php

namespace Tests\Feature\Location;

use App\Enums\Location\LocationAnomaly;
use App\Enums\Settings\SettingKey;
use App\Events\Realtime\BookingLocationUpdated;
use App\Models\ProviderLastLocation;
use App\Models\ProviderLocation;
use App\Services\Location\LocationIngestService;
use App\Services\Settings\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsDispatch;
use Tests\TestCase;

/**
 * POST /provider/location and LocationIngestService: validation, impossible jumps flagged (not dropped), late samples,
 * history sampling, tracking state and the booking channel broadcast.
 */
class LocationIngestTest extends TestCase
{
    use BuildsDispatch;
    use RefreshDatabase;

    private function upload(array $samples)
    {
        return $this->postJson(route('api.v1.provider.location.store'), ['samples' => $samples]);
    }

    public function test_a_sample_updates_the_last_location_and_reports_the_tracking_state(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);

        $this->upload([$this->samplePayload(30.0504, 31.2504, CarbonImmutable::now()->addSeconds(30))])
            ->assertOk()
            ->assertJsonPath('data.applied', 1)
            ->assertJsonPath('data.flagged', 0)
            ->assertJsonPath('data.tracking.state', 'idle')
            ->assertJsonPath('data.tracking.interval_seconds', 30);

        $last = ProviderLastLocation::query()->where('provider_profile_id', $provider->id)->firstOrFail();
        $this->assertEqualsWithDelta(30.0504, $last->point->lat, 1e-6);
        $this->assertEqualsWithDelta(31.2504, $last->point->lng, 1e-6);
        $this->assertSame(1, ProviderLastLocation::query()->where('provider_profile_id', $provider->id)->count());
    }

    public function test_an_impossible_jump_is_flagged_stored_and_does_not_move_the_provider(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);

        // ~9 km in 5 seconds is about 6,500 km/h.
        $this->upload([$this->samplePayload(30.13, 31.25, CarbonImmutable::now()->addSeconds(5))])
            ->assertOk()
            ->assertJsonPath('data.applied', 0)
            ->assertJsonPath('data.flagged', 1);

        $last = ProviderLastLocation::query()->where('provider_profile_id', $provider->id)->firstOrFail();
        $this->assertEqualsWithDelta(30.05, $last->point->lat, 1e-6);

        $flagged = ProviderLocation::query()->where('provider_profile_id', $provider->id)->whereNotNull('anomaly')->get();
        $this->assertCount(1, $flagged);
        $this->assertSame(LocationAnomaly::ImpossibleSpeed, $flagged[0]->anomaly);
        $this->assertEqualsWithDelta(30.13, $flagged[0]->point->lat, 1e-6);
    }

    public function test_the_speed_threshold_is_a_setting(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);
        // ~1.1 km in 10 s is 400 km/h: flagged at the default 200 km/h, fine at 1000 km/h.
        $payload = $this->samplePayload(30.06, 31.25, CarbonImmutable::now()->addSeconds(10));

        $this->upload([$payload])->assertOk()->assertJsonPath('data.flagged', 1);

        app(SettingsService::class)->set(SettingKey::LocationMaxSpeedKmh, 1000);
        $this->upload([$payload])->assertOk()->assertJsonPath('data.flagged', 0)->assertJsonPath('data.applied', 1);
    }

    public function test_gps_jitter_between_close_samples_is_not_an_anomaly(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);

        // 60 m in one second is 216 km/h but below the jitter floor.
        $this->upload([$this->samplePayload(30.05054, 31.25, CarbonImmutable::now()->addSecond())])
            ->assertOk()->assertJsonPath('data.flagged', 0)->assertJsonPath('data.applied', 1);
    }

    public function test_a_future_timestamp_is_flagged(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);

        $this->upload([$this->samplePayload(30.051, 31.25, CarbonImmutable::now()->addHour())])
            ->assertOk()->assertJsonPath('data.flagged', 1);

        $this->assertSame(LocationAnomaly::FutureTimestamp, ProviderLocation::query()->whereNotNull('anomaly')->firstOrFail()->anomaly);
    }

    public function test_a_batch_is_ordered_by_time_and_older_samples_do_not_move_the_provider_back(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);
        $now = CarbonImmutable::now();

        $this->upload([
            $this->samplePayload(30.056, 31.25, $now->addSeconds(30)),
            $this->samplePayload(30.052, 31.25, $now->addSeconds(10)),
            $this->samplePayload(30.054, 31.25, $now->addSeconds(20)),
            $this->samplePayload(30.04, 31.25, $now->subMinutes(2)),
        ])->assertOk()
            ->assertJsonPath('data.received', 4)
            ->assertJsonPath('data.applied', 3)
            ->assertJsonPath('data.stale', 1);

        $last = ProviderLastLocation::query()->where('provider_profile_id', $provider->id)->firstOrFail();
        $this->assertEqualsWithDelta(30.056, $last->point->lat, 1e-6);
    }

    public function test_the_history_keeps_one_sample_per_interval(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);
        $now = CarbonImmutable::now();
        ProviderLocation::query()->where('provider_profile_id', $provider->id)->delete();

        // The sign-in sample is gone; four samples 5 s apart with a 15 s interval store the first and the one 15 s later.
        $samples = [];

        foreach ([1, 5, 10, 16, 20] as $offset) {
            $samples[] = $this->samplePayload(30.05 + $offset * 0.00001, 31.25, $now->addSeconds($offset));
        }

        $this->upload($samples)->assertOk()->assertJsonPath('data.applied', 5);

        $this->assertSame(2, ProviderLocation::query()->where('provider_profile_id', $provider->id)->count());
    }

    public function test_invalid_coordinates_and_oversized_batches_are_rejected(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);

        $this->upload([$this->samplePayload(95, 31.25)])->assertStatus(422)->assertJsonValidationErrors(['samples.0.lat']);
        $this->upload([$this->samplePayload(30, 200)])->assertStatus(422)->assertJsonValidationErrors(['samples.0.lng']);
        $this->upload([$this->samplePayload(0, 0)])->assertStatus(422)->assertJsonValidationErrors(['samples.0.lat']);
        $this->upload([['lat' => 'abc', 'lng' => 31.2, 'recorded_at' => now()->toIso8601String()]])->assertStatus(422);
        $this->upload([])->assertStatus(422);
        $this->upload(array_fill(0, 21, $this->samplePayload()))->assertStatus(422)->assertJsonValidationErrors(['samples']);
        $this->postJson(route('api.v1.provider.location.store'), [])->assertStatus(422);
    }

    public function test_an_offline_provider_cannot_upload_locations(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->approvedProviderFor($category);
        $this->actingAsProvider($provider);

        $this->upload([$this->samplePayload()])->assertStatus(409)->assertJsonPath('errors._code', 'location_not_tracking');
        $this->assertSame(0, ProviderLastLocation::query()->count());
    }

    public function test_only_provider_tokens_may_upload(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider, 'customer');

        $this->upload([$this->samplePayload()])->assertForbidden();
    }

    public function test_the_endpoint_is_rate_limited(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->actingAsProvider($provider);
        app(SettingsService::class)->set(SettingKey::RateLimitLocationPerMinute, 3);

        foreach (range(1, 3) as $i) {
            $this->upload([$this->samplePayload(30.05, 31.25, CarbonImmutable::now()->addSeconds($i))])->assertOk();
        }

        $this->upload([$this->samplePayload()])->assertStatus(429);
    }

    public function test_a_provider_serving_a_booking_broadcasts_to_the_booking_channel_and_is_tracked_while_offline(): void
    {
        Event::fake([BookingLocationUpdated::class]);
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $this->fakeWorkload($this->busy('33333333-3333-4333-8333-333333333333'));
        $provider->forceFill(['is_online' => false])->save();
        $this->actingAsProvider($provider);

        $this->upload([$this->samplePayload(30.051, 31.25, CarbonImmutable::now()->addSeconds(30))])
            ->assertOk()->assertJsonPath('data.tracking.state', 'active')->assertJsonPath('data.tracking.interval_seconds', 5);

        Event::assertDispatched(BookingLocationUpdated::class, static fn (BookingLocationUpdated $event): bool => $event->bookingUuid === '33333333-3333-4333-8333-333333333333'
            && $event->sequence >= 1
            && abs($event->payload['lat'] - 30.051) < 1e-6
            && $event->broadcastOn()[0]->name === 'private-booking.33333333-3333-4333-8333-333333333333'
            && $event->broadcastAs() === 'booking.location_updated');
        $this->assertSame(1, ProviderLocation::query()->whereNotNull('booking_id')->count());
    }

    public function test_the_service_reports_idle_for_online_and_off_for_offline_providers(): void
    {
        ['category' => $category] = $this->cairo();
        $provider = $this->onlineProviderFor($category);
        $service = app(LocationIngestService::class);

        $this->assertSame('idle', $service->trackingState($provider)->value);
        $provider->forceFill(['is_online' => false]);
        $this->assertSame('off', $service->trackingState($provider)->value);
    }
}
