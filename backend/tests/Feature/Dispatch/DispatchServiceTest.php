<?php

namespace Tests\Feature\Dispatch;

use App\DTOs\Dispatch\DispatchableRequest;
use App\Enums\Dispatch\DispatchResponse;
use App\Enums\Provider\VehicleStatus;
use App\Events\Realtime\BookingRequested;
use App\Events\Realtime\BookingSearchExpanding;
use App\Jobs\Dispatch\DispatchBookingWave;
use App\Models\DispatchRecipient;
use App\Models\ProviderLastLocation;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Services\Booking\DispatchableBookingSource;
use App\Services\Dispatch\DispatchableRequestSource;
use App\Services\Dispatch\DispatchService;
use App\Services\Dispatch\FindEligibleProvidersService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsDispatch;
use Tests\TestCase;

/**
 * Dispatch against the minimal DispatchableRequest contract (no bookings table yet): who is found, wave radius
 * progression and batching, recipients, events on private channels, and the wave chain timing.
 */
class DispatchServiceTest extends TestCase
{
    use BuildsDispatch;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** ~ metres north of the pickup (30.05, 31.25): 0.001 deg latitude is about 111 m. */
    private function onlineAtKm(\App\Models\ServiceCategory $category, float $km): \App\Models\ProviderProfile
    {
        return $this->onlineProviderFor($category, 30.05 + $km / 111.2, 31.25);
    }

    private function finder(): FindEligibleProvidersService
    {
        return app(FindEligibleProvidersService::class);
    }

    public function test_it_finds_online_eligible_providers_within_the_radius_nearest_first(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $far = $this->onlineAtKm($category, 3.0);
        $near = $this->onlineAtKm($category, 0.5);
        $mid = $this->onlineAtKm($category, 1.5);
        $request = $this->requestFor($category, $city);

        $found = $this->finder()->find($request, 2.0, 10);

        $this->assertSame([$near->id, $mid->id], array_map(static fn ($p): int => $p->profile->id, $found));
        $this->assertEqualsWithDelta(500, $found[0]->distanceMeters, 20);
        $this->assertSame([$near->id, $mid->id, $far->id], array_map(static fn ($p): int => $p->profile->id, $this->finder()->find($request, 4.0, 10)));
        $this->assertCount(2, $this->finder()->find($request, 4.0, 2));
    }

    public function test_it_filters_providers_that_may_not_work_on_this_request(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $good = $this->onlineAtKm($category, 0.3);

        $offline = $this->onlineAtKm($category, 0.4);
        $offline->forceFill(['is_online' => false])->save();

        $suspended = $this->onlineAtKm($category, 0.5);
        $suspended->forceFill(['status' => \App\Enums\Provider\ProviderStatus::TemporarilySuspended])->save();

        $staleLocation = $this->onlineAtKm($category, 0.6);
        ProviderLastLocation::query()->where('provider_profile_id', $staleLocation->id)->update(['recorded_at' => now()->subMinutes(10)]);

        $vehicleLost = $this->onlineAtKm($category, 0.7);
        $vehicleLost->vehicles()->update(['status' => VehicleStatus::Rejected]);

        $otherService = $this->bookableCategory();
        $this->zoneOffering([$otherService], [], $city);
        $wrongService = $this->approvedProviderFor($otherService);
        $wrongService->forceFill(['is_online' => true, 'current_vehicle_id' => $wrongService->vehicles()->firstOrFail()->id])->save();
        app(\App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface::class)->upsert($wrongService, $this->sampleAt(30.0525), $city->id, now());

        $found = $this->finder()->find($this->requestFor($category, $city), 5.0, 10);

        $this->assertSame([$good->id], array_map(static fn ($p): int => $p->profile->id, $found));
    }

    public function test_a_provider_with_an_active_job_is_skipped_only_under_the_single_job_policy(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $this->onlineAtKm($category, 0.3);
        $this->fakeWorkload($this->busy());
        $request = $this->requestFor($category, $city);

        $this->assertSame([], $this->finder()->find($request, 2.0, 10));

        app(\App\Services\Settings\SettingsService::class)->set(\App\Enums\Settings\SettingKey::ProviderSingleActiveJob, false);
        $this->assertCount(1, app(FindEligibleProvidersService::class)->find($request, 2.0, 10));
    }

    public function test_vehicle_category_and_capacity_needs_of_the_request_are_respected(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $provider = $this->onlineAtKm($category, 0.3);
        $vehicle = $provider->vehicles()->firstOrFail();
        $vehicle->forceFill(['passenger_capacity' => 3])->save();

        $this->assertCount(1, $this->finder()->find($this->requestFor($category, $city, overrides: ['vehicle_category_ids' => [$vehicle->vehicle_category_id], 'passengers' => 3]), 2.0, 10));
        $this->assertSame([], $this->finder()->find($this->requestFor($category, $city, overrides: ['vehicle_category_ids' => [$vehicle->vehicle_category_id + 999]]), 2.0, 10));
        $this->assertSame([], $this->finder()->find($this->requestFor($category, $city, overrides: ['passengers' => 4]), 2.0, 10));
    }

    public function test_providers_already_dispatched_are_not_found_again(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $first = $this->onlineAtKm($category, 0.3);
        $this->onlineAtKm($category, 0.6);
        $request = $this->requestFor($category, $city);
        app(DispatchRecipientRepositoryInterface::class)->recordWave($request->bookingId, 1, [['provider_profile_id' => $first->id, 'distance_m' => 300]]);

        $found = $this->finder()->find($request, 2.0, 10);

        $this->assertCount(1, $found);
        $this->assertNotSame($first->id, $found[0]->profile->id);
    }

    public function test_wave_one_notifies_a_batch_on_private_provider_channels_and_records_recipients(): void
    {
        Event::fake([BookingRequested::class, BookingSearchExpanding::class]);
        Bus::fake([DispatchBookingWave::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $category->forceFill(['dispatch_batch_size' => 2, 'dispatch_radius_steps_km' => [2, 4, 7]])->save();
        $a = $this->onlineAtKm($category, 0.3);
        $b = $this->onlineAtKm($category, 0.9);
        $c = $this->onlineAtKm($category, 1.4);
        $request = $this->requestFor($category, $city);

        $result = app(DispatchService::class)->runWave($request, 1);

        $this->assertSame(2, $result->notified);
        $this->assertSame(2.0, $result->radiusKm);
        $recipients = DispatchRecipient::query()->where('booking_id', $request->bookingId)->orderBy('distance_m')->get();
        $this->assertSame([$a->id, $b->id], $recipients->pluck('provider_profile_id')->all());
        $this->assertSame([1, 1], $recipients->pluck('wave')->all());
        $this->assertSame(DispatchResponse::Pending, $recipients[0]->response);
        $this->assertNotNull($recipients[0]->notified_at);

        Event::assertDispatchedTimes(BookingRequested::class, 2);
        Event::assertDispatched(BookingRequested::class, static function (BookingRequested $event) use ($a, $request): bool {
            return $event->providerUuid === $a->uuid
                && $event->broadcastOn()[0]->name === 'private-provider.'.$a->uuid
                && $event->broadcastAs() === 'booking.requested'
                && $event->bookingUuid === $request->bookingUuid
                && $event->sequence >= 1
                && $event->payload['wave'] === 1
                && $event->payload['pickup_area'] === ['lat' => 30.05, 'lng' => 31.25]
                && ! array_key_exists('address', $event->payload) && ! array_key_exists('phone', $event->payload);
        });
        Event::assertNotDispatched(BookingSearchExpanding::class);

        // The third provider is in range of wave 1 but outside its batch: wave 2 takes it, with a wider radius.
        $second = app(DispatchService::class)->runWave($request, 2);

        $this->assertSame(1, $second->notified);
        $this->assertSame(4.0, $second->radiusKm);
        $this->assertSame($c->id, DispatchRecipient::query()->where('wave', 2)->firstOrFail()->provider_profile_id);
        Event::assertDispatched(BookingSearchExpanding::class, static fn (BookingSearchExpanding $event): bool => $event->payload === ['wave' => 2, 'radius_km' => 4.0]
            && $event->broadcastOn()[0]->name === 'private-booking.'.$request->bookingUuid);
    }

    public function test_the_radius_steps_follow_the_category_policy_and_the_last_step_repeats(): void
    {
        Event::fake([BookingRequested::class, BookingSearchExpanding::class]);
        Bus::fake([DispatchBookingWave::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $category->forceFill(['dispatch_radius_steps_km' => [1, 3]])->save();
        $this->onlineAtKm($category, 2.5);
        $request = $this->requestFor($category, $city);
        $service = app(DispatchService::class);

        $this->assertSame(0, $service->runWave($request, 1)->notified);
        $this->assertSame(3.0, $service->runWave($request, 2)->radiusKm);
        $this->assertSame(1, DispatchRecipient::query()->where('wave', 2)->count());
        $this->assertSame(3.0, $service->runWave($request, 5)->radiusKm);
    }

    public function test_the_platform_default_steps_apply_when_the_category_has_none(): void
    {
        Bus::fake([DispatchBookingWave::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $service = app(DispatchService::class);
        $request = $this->requestFor($category, $city);

        $this->assertSame([2.0, 4.0, 7.0, 7.0], array_map(static fn (int $wave): float => $service->runWave($request, $wave)->radiusKm, [1, 2, 3, 4]));
    }

    public function test_running_a_wave_twice_does_not_notify_the_same_providers_twice(): void
    {
        Event::fake([BookingRequested::class]);
        Bus::fake([DispatchBookingWave::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $this->onlineAtKm($category, 0.3);
        $request = $this->requestFor($category, $city);

        $this->assertSame(1, app(DispatchService::class)->runWave($request, 1)->notified);
        $this->assertSame(0, app(DispatchService::class)->runWave($request, 1)->notified);
        Event::assertDispatchedTimes(BookingRequested::class, 1);
    }

    public function test_the_next_wave_is_delayed_by_the_interval_and_only_while_the_search_is_open(): void
    {
        Bus::fake([DispatchBookingWave::class]);
        $now = CarbonImmutable::parse('2026-10-21 12:00:00');
        CarbonImmutable::setTestNow($now);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $service = app(DispatchService::class);

        $open = $this->requestFor($category, $city, 601, $now->addSeconds(100));
        $this->assertTrue($service->runWave($open, 1)->nextWaveScheduled);
        Bus::assertDispatched(DispatchBookingWave::class, static fn (DispatchBookingWave $job): bool => $job->bookingId === 601 && $job->wave === 2 && $job->delay === 30 && $job->queue === 'critical');

        // 100 s left, 30 s interval: waves at +30, +60, +90 fit; a wave that could only start after expiry is not queued.
        $closing = $this->requestFor($category, $city, 602, $now->addSeconds(20));
        $this->assertFalse($service->runWave($closing, 1)->nextWaveScheduled);
        Bus::assertNotDispatched(DispatchBookingWave::class, static fn (DispatchBookingWave $job): bool => $job->bookingId === 602);
    }

    public function test_an_expired_or_assigned_request_is_not_dispatched(): void
    {
        Event::fake([BookingRequested::class]);
        Bus::fake([DispatchBookingWave::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $this->onlineAtKm($category, 0.3);
        $service = app(DispatchService::class);

        $this->assertSame(0, $service->runWave($this->requestFor($category, $city, 701, CarbonImmutable::now()->subSecond()), 1)->notified);
        $this->assertSame(0, $service->runWave($this->requestFor($category, $city, 702, overrides: ['searching' => false]), 1)->notified);
        Event::assertNotDispatched(BookingRequested::class);
        Bus::assertNotDispatched(DispatchBookingWave::class);
    }

    public function test_the_wave_job_reads_the_request_again_and_runs_the_wave(): void
    {
        Event::fake([BookingRequested::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();
        $this->onlineAtKm($category, 0.3);
        $request = $this->requestFor($category, $city, 801);
        $this->app->bind(DispatchableRequestSource::class, fn () => new class($request) implements DispatchableRequestSource
        {
            public function __construct(private readonly DispatchableRequest $request) {}

            public function find(int $bookingId): ?DispatchableRequest
            {
                return $bookingId === $this->request->bookingId ? $this->request : null;
            }
        });
        Bus::fake([DispatchBookingWave::class]);

        (new DispatchBookingWave(801, 1))->handle(app(DispatchableRequestSource::class), app(DispatchService::class));
        (new DispatchBookingWave(999, 1))->handle(app(DispatchableRequestSource::class), app(DispatchService::class));

        Event::assertDispatchedTimes(BookingRequested::class, 1);
        $this->assertSame(1, DispatchRecipient::query()->count());
    }

    public function test_the_job_is_unique_per_booking_and_wave_and_start_queues_wave_one(): void
    {
        Bus::fake([DispatchBookingWave::class]);
        ['category' => $category, 'city' => $city] = $this->cairo();

        app(DispatchService::class)->start($this->requestFor($category, $city, 901));

        Bus::assertDispatched(DispatchBookingWave::class, static fn (DispatchBookingWave $job): bool => $job->wave === 1 && $job->uniqueId() === 'dispatch-wave:901:1:1');
    }

    public function test_the_source_is_bound_to_bookings_and_a_vanished_booking_stops_the_job_quietly(): void
    {
        $this->assertInstanceOf(DispatchableBookingSource::class, app(DispatchableRequestSource::class));
        $this->assertNull(app(DispatchableRequestSource::class)->find(999_999));

        (new DispatchBookingWave(999_999, 1))->handle(app(DispatchableRequestSource::class), app(DispatchService::class));

        $this->assertSame(0, DispatchRecipient::query()->count());
    }

    public function test_recipient_responses_can_be_recorded(): void
    {
        ['category' => $category, 'city' => $city] = $this->cairo();
        $provider = $this->onlineAtKm($category, 0.3);
        $booking = $this->requestFor($category, $city, 1);
        $recipients = app(DispatchRecipientRepositoryInterface::class);
        $recipients->recordWave($booking->bookingId, 1, [['provider_profile_id' => $provider->id, 'distance_m' => 300]]);

        $recipients->markSeen(1, $provider->id);
        $recipients->respond(1, $provider->id, DispatchResponse::Declined);
        $row = $recipients->findRecipient(1, $provider->id);

        $this->assertNotNull($row->seen_at);
        $this->assertSame(DispatchResponse::Declined, $row->response);
        $this->assertNotNull($row->responded_at);
        $this->assertSame(1, $recipients->forBooking(1)->count());
        $this->assertSame(1, $recipients->countForWave(1, 1));
    }
}
