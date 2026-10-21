<?php

namespace Tests\Feature\Booking;

use App\DTOs\Booking\OffersSummary;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\OfferInvalidationReason;
use App\Enums\Settings\SettingKey;
use App\Events\Booking\BookingEnteredSearch;
use App\Events\Realtime\BookingStatusChanged;
use App\Jobs\Booking\ExpireBookingSearch;
use App\Jobs\Dispatch\DispatchBookingWave;
use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Services\Booking\BookingExpiryService;
use App\Services\Booking\BookingNotifier;
use App\Services\Booking\ScheduledBookingReleaseService;
use App\Services\Dispatch\DispatchableRequestSource;
use App\Services\Dispatch\DispatchService;
use App\Services\Settings\SettingsService;
use App\Support\Booking\BookingStatusGate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

/**
 * Search windows end by themselves, with no client involved: the delayed job at the exact second and the 5-second
 * sweep as backstop, both idempotent and never early (Plan §2.3, I2).
 */
class BookingExpiryTest extends TestCase
{
    use BuildsBookings;
    use RefreshDatabase;

    private ServiceCategory $category;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        ['category' => $this->category, 'customer' => $this->customer] = $this->bookingWorld();
    }

    private function createBooking(): Booking
    {
        $this->actingAsCustomer($this->customer);
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertCreated();

        return Booking::query()->latest('id')->firstOrFail();
    }

    public function test_entering_search_schedules_the_dispatch_and_the_exact_expiry(): void
    {
        $booking = $this->createBooking();

        Bus::assertDispatched(DispatchBookingWave::class, static fn (DispatchBookingWave $job): bool => $job->bookingId === $booking->id && $job->wave === 1 && $job->generation === 1);
        Bus::assertDispatched(ExpireBookingSearch::class, static fn (ExpireBookingSearch $job): bool => $job->bookingId === $booking->id
            && $job->delay !== null
            && abs(\Illuminate\Support\Carbon::parse($job->delay)->timestamp - $booking->search_expires_at->timestamp) <= 1);
    }

    public function test_an_unanswered_search_expires_without_the_client(): void
    {
        $booking = $this->createBooking();
        Event::fake([BookingStatusChanged::class]);
        $this->app['auth']->forgetGuards();
        $notifier = $this->mock(BookingNotifier::class);
        $notifier->shouldReceive('searchExpired')->once()->withArgs(static fn (Booking $expired): bool => $expired->id === $booking->id);

        $this->travelTo($booking->search_expires_at->addSecond());
        $this->artisan('bookings:expire-searches')->assertSuccessful();

        $fresh = $booking->fresh();
        $this->assertSame(BookingStatus::ExpiredNoProvider, $fresh->status);
        $event = BookingStatusEvent::query()->where('booking_id', $booking->id)->where('to_status', 'expired_no_provider')->sole();
        $this->assertSame('system', $event->actor_type);
        $this->assertNull($event->actor_id);
        $this->assertSame('search_expired', $event->reason);
        Event::assertDispatched(BookingStatusChanged::class, static fn (BookingStatusChanged $changed): bool => $changed->payload['status'] === 'expired_no_provider');
    }

    public function test_the_delayed_job_does_the_same_and_does_not_need_the_sweep(): void
    {
        $booking = $this->createBooking();
        $this->travelTo($booking->search_expires_at->addSecond());

        (new ExpireBookingSearch($booking->id))->handle(app(BookingExpiryService::class));

        $this->assertSame(BookingStatus::ExpiredNoProvider, $booking->fresh()->status);
    }

    public function test_nothing_expires_early(): void
    {
        $booking = $this->createBooking();
        $this->travelTo($booking->search_expires_at->subSeconds(5));

        $this->artisan('bookings:expire-searches')->assertSuccessful();
        (new ExpireBookingSearch($booking->id))->handle(app(BookingExpiryService::class));

        $this->assertSame(BookingStatus::SearchingOffers, $booking->fresh()->status);
    }

    public function test_expiring_twice_changes_nothing_the_second_time(): void
    {
        $booking = $this->createBooking();
        $this->travelTo($booking->search_expires_at->addSecond());
        $service = app(BookingExpiryService::class);

        $this->assertTrue($service->expire($booking->id));
        $this->assertFalse($service->expire($booking->id));
        $this->assertSame(0, $service->sweep());

        $this->assertSame(1, BookingStatusEvent::query()->where('booking_id', $booking->id)->where('to_status', 'expired_no_provider')->count());
    }

    public function test_offers_received_expires_too_and_offers_are_closed(): void
    {
        $log = [];
        $this->app->instance(BookingOfferRepositoryInterface::class, new class($log) implements BookingOfferRepositoryInterface
        {
            public function __construct(public array &$log) {}

            public function summary(Booking $booking): OffersSummary
            {
                return new OffersSummary;
            }

            public function invalidateActive(Booking $booking, OfferInvalidationReason $reason): int
            {
                $this->log[] = [$booking->id, $reason];

                return 1;
            }
        });
        $booking = $this->createBooking();
        BookingStatusGate::run(fn () => $booking->forceFill(['status' => BookingStatus::OffersReceived])->save());
        $this->travelTo($booking->search_expires_at->addSecond());

        $this->assertSame(1, app(BookingExpiryService::class)->sweep());

        $this->assertSame(BookingStatus::ExpiredNoProvider, $booking->fresh()->status);
        $this->assertSame([[$booking->id, OfferInvalidationReason::SearchExpired]], $log);
    }

    public function test_bookings_that_moved_on_are_left_alone(): void
    {
        $booking = $this->createBooking();
        BookingStatusGate::run(fn () => $booking->forceFill(['status' => BookingStatus::ProviderSelected])->save());
        $this->travelTo($booking->search_expires_at->addMinute());

        $this->assertSame(0, app(BookingExpiryService::class)->sweep());
        $this->assertSame(BookingStatus::ProviderSelected, $booking->fresh()->status);
    }

    public function test_an_old_delayed_job_does_not_end_a_search_whose_window_was_extended(): void
    {
        $booking = $this->createBooking();
        $oldEnd = $booking->search_expires_at;
        $this->travelTo($oldEnd->subSeconds(10));
        $this->patchJson('/api/v1/bookings/'.$booking->uuid.'/request', ['proposed_fare_minor' => 7000], $this->idempotency())->assertOk();
        $this->travelTo($oldEnd->addSecond());

        (new ExpireBookingSearch($booking->id))->handle(app(BookingExpiryService::class));

        $this->assertSame(BookingStatus::SearchingOffers, $booking->fresh()->status);
    }

    public function test_dispatch_stops_when_the_search_is_over(): void
    {
        $booking = $this->createBooking();
        $this->travelTo($booking->search_expires_at->addSecond());
        app(BookingExpiryService::class)->expire($booking->id);

        $request = app(DispatchableRequestSource::class)->find($booking->id);
        $this->assertFalse($request?->searching);
        $this->assertSame(0, app(DispatchService::class)->runWave($request, 1)->notified);
    }

    public function test_the_sweep_runs_every_five_seconds(): void
    {
        $event = collect(app(Schedule::class)->events())->first(static fn ($event): bool => str_contains((string) $event->command, 'bookings:expire-searches'));

        $this->assertNotNull($event);
        $this->assertSame(5, $event->repeatSeconds);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_scheduled_bookings_open_their_search_when_due(): void
    {
        app(SettingsService::class)->set(SettingKey::ScheduledSearchLeadMinutes, 20);
        $this->actingAsCustomer($this->customer);
        $at = now()->addDays(2);
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category, ['booking_type' => 'scheduled', 'scheduled_at' => $at->toIso8601String()]), $this->idempotency())->assertCreated();
        $booking = Booking::query()->firstOrFail();
        $this->assertSame(BookingStatus::Draft, $booking->status);

        $this->assertSame(0, app(ScheduledBookingReleaseService::class)->releaseDue());
        $this->assertSame(BookingStatus::Draft, $booking->fresh()->status);

        Event::fake([BookingEnteredSearch::class]);
        $this->travelTo($at->subMinutes(19));
        $this->artisan('bookings:expire-searches')->assertSuccessful();

        $released = $booking->fresh();
        $this->assertSame(BookingStatus::SearchingOffers, $released->status);
        $this->assertGreaterThan(now()->timestamp, $released->search_expires_at->timestamp);
        Event::assertDispatched(BookingEnteredSearch::class, static fn (BookingEnteredSearch $event): bool => $event->bookingId === $booking->id && ! $event->restart);

        $this->assertSame(0, app(ScheduledBookingReleaseService::class)->releaseDue());
    }

    public function test_a_cancelled_scheduled_booking_is_not_released(): void
    {
        $booking = Booking::factory()->forCategory($this->category)->status(BookingStatus::CancelledByCustomer)->create(['scheduled_at' => now()->addMinutes(5), 'search_expires_at' => null]);

        $this->assertSame(0, app(ScheduledBookingReleaseService::class)->releaseDue());
        $this->assertSame(BookingStatus::CancelledByCustomer, $booking->fresh()->status);
    }
}
