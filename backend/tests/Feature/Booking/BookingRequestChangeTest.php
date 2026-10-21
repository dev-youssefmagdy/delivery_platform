<?php

namespace Tests\Feature\Booking;

use App\DTOs\Booking\OffersSummary;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\OfferInvalidationReason;
use App\Enums\Identity\UserStatus;
use App\Events\Booking\BookingEnteredSearch;
use App\Events\Realtime\BookingRequestChanged;
use App\Jobs\Dispatch\DispatchBookingWave;
use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\DispatchRecipient;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Services\Dispatch\DispatchableRequestSource;
use App\Services\Dispatch\DispatchService;
use App\Support\Booking\BookingStatusGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

/**
 * PATCH /bookings/{booking}/request: change the route or main details while searching; active offers are
 * invalidated and dispatch starts over (SRS §6.5).
 */
class BookingRequestChangeTest extends TestCase
{
    use BuildsBookings;
    use RefreshDatabase;

    private ServiceCategory $category;

    private User $customer;

    private Booking $booking;

    /** @var list<OfferInvalidationReason> */
    private array $invalidations = [];

    protected function setUp(): void
    {
        parent::setUp();
        ['category' => $this->category, 'customer' => $this->customer] = $this->bookingWorld();
        $this->actingAsCustomer($this->customer);
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertCreated();
        $this->booking = Booking::query()->firstOrFail();

        $recorder = new class($this->invalidations) implements BookingOfferRepositoryInterface
        {
            /** @param list<OfferInvalidationReason> $log */
            public function __construct(public array &$log) {}

            public function summary(Booking $booking): OffersSummary
            {
                return new OffersSummary(2, 4800);
            }

            public function invalidateActive(Booking $booking, OfferInvalidationReason $reason): int
            {
                $this->log[] = $reason;

                return 2;
            }
        };
        $this->app->instance(BookingOfferRepositoryInterface::class, $recorder);
    }

    private function change(array $body, ?string $key = null, ?Booking $booking = null)
    {
        return $this->patchJson('/api/v1/bookings/'.($booking ?? $this->booking)->uuid.'/request', $body, $this->idempotency($key));
    }

    public function test_changing_the_fare_invalidates_offers_restarts_the_search_and_dispatch(): void
    {
        Event::fake([BookingRequestChanged::class, BookingEnteredSearch::class]);
        $before = $this->booking->search_expires_at;
        $this->travel(2)->minutes();

        $this->change(['proposed_fare_minor' => 7000])->assertOk()
            ->assertJsonPath('data.proposed_fare.amount_minor', 7000)
            ->assertJsonPath('data.request_version', 2)
            ->assertJsonPath('data.status', 'searching_offers');

        $booking = $this->booking->fresh();
        $this->assertSame(7000, $booking->proposed_fare_minor);
        $this->assertSame(2, $booking->request_version);
        $this->assertGreaterThan($before->timestamp, $booking->search_expires_at->timestamp);
        $this->assertSame([OfferInvalidationReason::RequestChanged], $this->invalidations);

        $note = BookingStatusEvent::query()->where('booking_id', $booking->id)->where('reason', 'request_changed')->sole();
        $this->assertSame($note->from_status, $note->to_status);
        $this->assertSame(['proposed_fare'], $note->metadata['changed']);
        $this->assertSame('customer', $note->actor_type);

        Event::assertDispatched(BookingRequestChanged::class, static fn (BookingRequestChanged $event): bool => $event->payload['request_version'] === 2 && $event->broadcastAs() === 'booking.request_changed');
        Event::assertDispatched(BookingEnteredSearch::class, static fn (BookingEnteredSearch $event): bool => $event->restart === true);
    }

    public function test_dispatch_starts_over_for_the_new_version_and_old_waves_stop(): void
    {
        $recipient = DispatchRecipient::query()->create(['booking_id' => $this->booking->id, 'provider_profile_id' => $this->makeProvider()->id, 'wave' => 1, 'distance_m' => 100, 'notified_at' => now()]);

        $this->change(['passengers' => 3, 'service_details' => ['luggage' => 0]])->assertOk();

        $this->assertModelMissing($recipient);
        Bus::assertDispatched(DispatchBookingWave::class, static fn (DispatchBookingWave $job): bool => $job->wave === 1 && $job->generation === 2);

        // A wave job queued before the change reads the new request version and stops.
        $stale = new DispatchBookingWave($this->booking->id, 2, 1);
        $stale->handle(app(DispatchableRequestSource::class), app(DispatchService::class));
        $this->assertSame(0, DispatchRecipient::query()->count());
        $this->assertSame(2, app(DispatchableRequestSource::class)->find($this->booking->id)?->generation);
    }

    public function test_offers_received_goes_back_to_searching(): void
    {
        BookingStatusGate::run(fn () => $this->booking->forceFill(['status' => BookingStatus::OffersReceived])->save());

        $this->change(['proposed_fare_minor' => 6500])->assertOk()->assertJsonPath('data.status', 'searching_offers');

        $this->assertSame(BookingStatus::SearchingOffers, $this->booking->fresh()->status);
        $this->assertTrue(BookingStatusEvent::query()->where('booking_id', $this->booking->id)->where('from_status', 'offers_received')->where('to_status', 'searching_offers')->exists());
    }

    public function test_moving_the_pickup_re_resolves_the_zone_and_recomputes_the_estimate(): void
    {
        $oldEstimate = $this->booking->reference_estimate['recommended_minor'];

        $this->change(['dropoff' => ['lat' => 30.095, 'lng' => 31.295, 'address' => ['line' => 'Farther away']], 'stops' => [['lat' => 30.06, 'lng' => 31.26]]])->assertOk();

        $booking = $this->booking->fresh()->load('stops');
        $this->assertGreaterThan($oldEstimate, $booking->reference_estimate['recommended_minor']);
        $this->assertSame(1, $booking->stops->count());
        $this->assertSame('Farther away', $booking->dropoff_address['line']);
        $this->assertSame(['dropoff', 'stops'], BookingStatusEvent::query()->where('reason', 'request_changed')->sole()->metadata['changed']);

        $this->change(['stops' => []])->assertOk();
        $this->assertSame(0, $this->booking->fresh()->stops()->count());
    }

    public function test_the_new_request_is_validated_like_a_new_one(): void
    {
        $this->change(['pickup' => ['lat' => 40.0, 'lng' => 10.0]])->assertStatus(422)->assertJsonPath('errors._code', 'outside_service_area');
        $this->change(['proposed_fare_minor' => 100])->assertStatus(422)->assertJsonPath('errors._code', 'fare_below_minimum');
        $this->change(['passengers' => 9])->assertStatus(422);
        $this->change(['service_details' => ['bogus' => 1]])->assertStatus(422)->assertJsonValidationErrors(['service_details.bogus']);
        $this->change(['scheduled_at' => now()->addDay()->toIso8601String()])->assertStatus(422)->assertJsonPath('errors._code', 'scheduled_at_not_allowed');

        $booking = $this->booking->fresh();
        $this->assertSame(1, $booking->request_version);
        $this->assertSame(5000, $booking->proposed_fare_minor);
        $this->assertSame([], $this->invalidations);
    }

    public function test_an_empty_patch_and_a_missing_key_are_refused(): void
    {
        $this->change([])->assertStatus(422)->assertJsonValidationErrors(['request']);
        $this->patchJson('/api/v1/bookings/'.$this->booking->uuid.'/request', ['proposed_fare_minor' => 6000])->assertStatus(422)->assertJsonPath('errors._code', 'idempotency_key_required');
        $this->assertSame(1, $this->booking->fresh()->request_version);
    }

    public function test_the_request_cannot_change_once_a_provider_is_selected_or_the_search_ended(): void
    {
        foreach ([BookingStatus::ProviderSelected, BookingStatus::ExpiredNoProvider, BookingStatus::CancelledByCustomer, BookingStatus::InProgress, BookingStatus::Completed] as $status) {
            $booking = Booking::factory()->forCustomer($this->customer)->forCategory($this->category)->status($status)->create();

            $this->change(['proposed_fare_minor' => 6000], booking: $booking)->assertStatus(409)->assertJsonPath('errors._code', 'booking_not_editable');
            $this->assertSame(5000, $booking->fresh()->proposed_fare_minor);
        }
    }

    public function test_the_same_key_replays_the_change_instead_of_applying_it_twice(): void
    {
        $key = (string) \Illuminate\Support\Str::uuid();

        $this->change(['proposed_fare_minor' => 7000], $key)->assertOk();
        $this->change(['proposed_fare_minor' => 7000], $key)->assertOk()->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame(2, $this->booking->fresh()->request_version);
        $this->assertCount(1, $this->invalidations);
    }

    public function test_a_restricted_customer_cannot_edit(): void
    {
        $this->customer->forceFill(['status' => UserStatus::Restricted])->save();
        $this->actingAsCustomer($this->customer);

        $this->change(['proposed_fare_minor' => 7000])->assertStatus(403)->assertJsonPath('errors._code', 'account_restricted');
    }

    public function test_the_category_booking_type_and_payment_cannot_be_changed(): void
    {
        $other = $this->rideCategory();
        $this->change(['service_category_id' => $other->id, 'booking_type' => 'scheduled', 'payment_method' => 'card', 'status' => 'completed', 'proposed_fare_minor' => 6000])->assertOk();

        $booking = $this->booking->fresh();
        $this->assertSame($this->category->id, $booking->service_category_id);
        $this->assertSame('on_demand', $booking->booking_type->value);
        $this->assertSame('cash', $booking->payment_method->value);
        $this->assertSame(BookingStatus::SearchingOffers, $booking->status);
        $this->assertSame(6000, $booking->proposed_fare_minor);
    }
}
