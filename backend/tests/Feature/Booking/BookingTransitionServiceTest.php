<?php

namespace Tests\Feature\Booking;

use App\DTOs\Booking\TransitionContext;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\ActorType;
use App\Enums\Provider\VehicleStatus;
use App\Enums\Settings\SettingKey;
use App\Events\Booking\BookingEnteredSearch;
use App\Events\Realtime\BookingStatusChanged;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\City;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\Vehicle;
use App\Services\Booking\BookingTransitionService;
use App\Services\Settings\SettingsService;
use App\Support\Booking\BookingStatusGate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

/**
 * BookingTransitionService (ADR-16): the whole 15 x 15 matrix through the real service, every guard, the event
 * history and the rule that nothing else may write `bookings.status`.
 */
class BookingTransitionServiceTest extends TestCase
{
    use BuildsBookings;
    use RefreshDatabase;

    private ServiceCategory $category;

    private ProviderProfile $provider;

    private City $city;

    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBookingReferenceData();
        ['category' => $this->category, 'city' => $this->city] = $this->cairo();
        $this->provider = $this->onlineProviderFor($this->category);
        $this->customerId = $this->customer()->id;
        app(SettingsService::class)->set(SettingKey::ProviderSingleActiveJob, false);
    }

    private function service(): BookingTransitionService
    {
        return app(BookingTransitionService::class);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function bookingIn(BookingStatus $status, array $state = []): Booking
    {
        $assigned = $status->isAssigned();

        $booking = Booking::factory()->forCategory($this->category)->status($status)->state($state + [
            'customer_id' => $this->customerId,
            'city_id' => $this->city->id,
            'provider_id' => $assigned ? $this->provider->id : null,
            'search_expires_at' => $status->isSearching() ? now()->subMinute() : null,
            'policy_snapshot' => ['dispatch' => ['search_ttl_seconds' => 300, 'auto_redispatch' => true], 'safety' => ['pin_required' => true, 'pickup_proof_required' => true, 'delivery_proof_types' => ['photo']]],
        ])->create();

        if (! array_key_exists('selected_offer_id', $state) && ($assigned || in_array($status, [BookingStatus::CompletedPendingPayment, BookingStatus::Completed, BookingStatus::Disputed], true))) {
            $booking->forceFill(['selected_offer_id' => $this->offerFor($booking), 'agreed_fare_minor' => 5000])->save();
        }

        return $booking;
    }

    /**
     * A real offer row of the booking (selected_offer_id is a foreign key), made by the provider of the test.
     */
    private function offerFor(Booking $booking): int
    {
        $vehicleId = Vehicle::query()->where('provider_profile_id', $this->provider->id)->value('id')
            ?? $this->vehicleFor($this->provider, VehicleStatus::Approved, ['vehicle_category_id' => $this->category->vehicleCategories()->firstOrFail()->id])->id;

        return (int) DB::table('booking_offers')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'booking_id' => $booking->id,
            'provider_profile_id' => $this->provider->id,
            'vehicle_id' => $vehicleId,
            'kind' => 'accept',
            'status' => 'accepted',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * A context that satisfies the guard of the target for this booking, so only the graph decides.
     */
    private function satisfying(Booking $booking, BookingStatus $to): TransitionContext
    {
        return match ($to) {
            BookingStatus::ProviderSelected => new TransitionContext(ActorType::System, providerId: $this->provider->id, offerId: $this->offerFor($booking)),
            BookingStatus::ProviderHeadingToPickup, BookingStatus::ProviderArrived, BookingStatus::InProgress, BookingStatus::CompletedPendingPayment, BookingStatus::Completed => new TransitionContext(ActorType::Provider, $booking->provider_id, pinVerified: true, proofsSatisfied: true),
            BookingStatus::CancelledByCustomer => new TransitionContext(ActorType::Customer, $booking->customer_id),
            BookingStatus::CancelledByProvider => new TransitionContext(ActorType::Provider, $booking->provider_id, 'Vehicle broke down'),
            BookingStatus::CancelledByOperations => new TransitionContext(ActorType::Admin, 1, 'Fraud suspected'),
            BookingStatus::SearchingOffers, BookingStatus::DeliveryAttemptFailed => $booking->provider_id !== null ? new TransitionContext(ActorType::Provider, $booking->provider_id) : TransitionContext::system('test'),
            default => TransitionContext::system('test'),
        };
    }

    public function test_every_pair_of_statuses_is_decided_by_the_graph(): void
    {
        Event::fake([BookingStatusChanged::class, BookingEnteredSearch::class]);
        $allowed = 0;
        $refused = 0;

        foreach (BookingStatus::cases() as $from) {
            foreach (BookingStatus::cases() as $to) {
                $booking = $this->bookingIn($from);

                if ($from->canTransitionTo($to)) {
                    $moved = $this->service()->transition($booking, $to, $this->satisfying($booking, $to));
                    $this->assertSame($to, $moved->status, "{$from->value} -> {$to->value}");
                    $this->assertSame($to, $booking->fresh()->status);
                    $allowed++;

                    continue;
                }

                try {
                    $this->service()->transition($booking, $to, $this->satisfying($booking, $to));
                    $this->fail("{$from->value} -> {$to->value} must be refused");
                } catch (ConflictException $exception) {
                    $this->assertSame('booking_transition_invalid', $exception->errorCode, "{$from->value} -> {$to->value}");
                }

                $this->assertSame($from, $booking->fresh()->status, "{$from->value} must not change on a refused transition");
                $this->assertSame(0, BookingStatusEvent::query()->where('booking_id', $booking->id)->count(), 'a refused transition writes no event');
                $refused++;
            }
        }

        $this->assertSame(225, $allowed + $refused);
        $this->assertGreaterThan(30, $allowed);
    }

    public function test_a_transition_records_an_event_with_the_next_sequence_number_and_broadcasts(): void
    {
        Event::fake([BookingStatusChanged::class]);
        $draft = $this->bookingIn(BookingStatus::Draft);

        $this->service()->recordCreation($draft, TransitionContext::system('created'));
        $moved = $this->service()->transition($draft, BookingStatus::SearchingOffers, TransitionContext::system('go'));

        $events = BookingStatusEvent::query()->where('booking_id', $draft->id)->orderBy('sequence')->get();
        $this->assertCount(2, $events);
        $this->assertNull($events[0]->from_status);
        $this->assertSame(BookingStatus::Draft, $events[0]->to_status);
        $this->assertSame(BookingStatus::Draft, $events[1]->from_status);
        $this->assertSame(BookingStatus::SearchingOffers, $events[1]->to_status);
        $this->assertSame([1, 2], $events->pluck('sequence')->all());
        $this->assertSame(2, $moved->sequence);
        $this->assertSame(1, $moved->lock_version);
        $this->assertNotNull($moved->search_expires_at);

        Event::assertDispatched(BookingStatusChanged::class, static fn (BookingStatusChanged $event): bool => $event->bookingUuid === $draft->uuid
            && $event->sequence === 2
            && $event->payload['status'] === 'searching_offers'
            && $event->broadcastOn()[0]->name === 'private-booking.'.$draft->uuid);
    }

    public function test_entering_search_starts_dispatch_only_from_draft_or_after_an_assignment(): void
    {
        Event::fake([BookingStatusChanged::class, BookingEnteredSearch::class]);

        $this->service()->transition($this->bookingIn(BookingStatus::Draft), BookingStatus::SearchingOffers, TransitionContext::system());
        Event::assertDispatched(BookingEnteredSearch::class, static fn (BookingEnteredSearch $event): bool => $event->restart === false);

        $assigned = $this->bookingIn(BookingStatus::ProviderSelected);
        $this->service()->transition($assigned, BookingStatus::SearchingOffers, new TransitionContext(ActorType::Provider, $assigned->provider_id));
        Event::assertDispatched(BookingEnteredSearch::class, static fn (BookingEnteredSearch $event): bool => $event->restart === true);
        $this->assertNull($assigned->fresh()->provider_id);
        $this->assertNull($assigned->fresh()->selected_offer_id);

        Event::fake([BookingStatusChanged::class, BookingEnteredSearch::class]);
        $offers = $this->bookingIn(BookingStatus::OffersReceived, ['search_expires_at' => now()->addMinute()]);
        $before = $offers->search_expires_at;
        $this->service()->transition($offers, BookingStatus::SearchingOffers, TransitionContext::system());
        Event::assertNotDispatched(BookingEnteredSearch::class);
        $this->assertEquals($before->timestamp, $offers->fresh()->search_expires_at->timestamp);
    }

    public function test_the_search_cannot_end_before_its_window_does(): void
    {
        $booking = $this->bookingIn(BookingStatus::SearchingOffers, ['search_expires_at' => now()->addMinute()]);

        $this->expectExceptionObject(BusinessRuleViolation::make('booking_search_not_expired'));
        $this->service()->transition($booking, BookingStatus::ExpiredNoProvider, TransitionContext::system());
    }

    public function test_the_search_ends_once_the_window_is_over_and_operations_may_end_it_earlier(): void
    {
        $over = $this->bookingIn(BookingStatus::SearchingOffers, ['search_expires_at' => now()->subSecond()]);
        $this->assertSame(BookingStatus::ExpiredNoProvider, $this->service()->transition($over, BookingStatus::ExpiredNoProvider, TransitionContext::system())->status);

        $open = $this->bookingIn(BookingStatus::SearchingOffers, ['search_expires_at' => now()->addMinutes(3)]);
        $this->assertSame(BookingStatus::ExpiredNoProvider, $this->service()->transition($open, BookingStatus::ExpiredNoProvider, new TransitionContext(ActorType::Admin, 1, 'Closed by operations'))->status);

        $clock = $this->bookingIn(BookingStatus::SearchingOffers, ['search_expires_at' => now()->addMinutes(3)]);
        $this->assertSame(BookingStatus::ExpiredNoProvider, $this->service()->transition($clock, BookingStatus::ExpiredNoProvider, new TransitionContext(ActorType::System, at: CarbonImmutable::now()->addMinutes(4)))->status);
    }

    public function test_selecting_a_provider_needs_the_offer_an_eligible_provider_and_no_assignment(): void
    {
        $booking = $this->bookingIn(BookingStatus::SearchingOffers);

        $offerId = $this->offerFor($booking);

        foreach ([[null, $offerId], [$this->provider->id, null]] as [$provider, $offer]) {
            try {
                $this->service()->transition($booking, BookingStatus::ProviderSelected, new TransitionContext(ActorType::System, providerId: $provider, offerId: $offer));
                $this->fail('incomplete selection must be refused');
            } catch (BusinessRuleViolation $exception) {
                $this->assertSame('booking_selection_incomplete', $exception->errorCode);
            }
        }

        $ineligible = $this->makeProvider();

        try {
            $this->service()->transition($booking, BookingStatus::ProviderSelected, new TransitionContext(ActorType::System, providerId: $ineligible->id, offerId: $offerId));
            $this->fail('an ineligible provider must be refused');
        } catch (BusinessRuleViolation $exception) {
            $this->assertSame('booking_provider_not_eligible', $exception->errorCode);
        }

        $selected = $this->service()->transition($booking, BookingStatus::ProviderSelected, new TransitionContext(ActorType::System, providerId: $this->provider->id, offerId: $offerId));
        $this->assertSame($this->provider->id, $selected->provider_id);
        $this->assertSame($offerId, $selected->selected_offer_id);

        // A booking that already has a provider is a conflict, whatever its status says.
        $racing = $this->bookingIn(BookingStatus::SearchingOffers, ['provider_id' => $this->provider->id]);
        $this->expectExceptionObject(ConflictException::make('booking_already_assigned'));
        $this->service()->transition($racing, BookingStatus::ProviderSelected, new TransitionContext(ActorType::System, providerId: $this->provider->id, offerId: 100));
    }

    public function test_only_the_assigned_provider_may_drive_the_trip_forward(): void
    {
        $booking = $this->bookingIn(BookingStatus::ProviderSelected);
        $other = $this->makeProvider();

        $this->expectException(ForbiddenActionException::class);
        $this->service()->transition($booking, BookingStatus::ProviderHeadingToPickup, new TransitionContext(ActorType::Provider, $other->id));
    }

    public function test_the_trip_cannot_start_without_the_pin_and_the_pickup_proof(): void
    {
        $booking = $this->bookingIn(BookingStatus::ProviderArrived);
        $provider = new TransitionContext(ActorType::Provider, $this->provider->id);

        try {
            $this->service()->transition($booking, BookingStatus::InProgress, $provider);
            $this->fail('PIN must be required');
        } catch (BusinessRuleViolation $exception) {
            $this->assertSame('booking_pin_required', $exception->errorCode);
        }

        try {
            $this->service()->transition($booking, BookingStatus::InProgress, new TransitionContext(ActorType::Provider, $this->provider->id, pinVerified: true));
            $this->fail('the pickup proof must be required');
        } catch (BusinessRuleViolation $exception) {
            $this->assertSame('booking_proof_required', $exception->errorCode);
        }

        $started = $this->service()->transition($booking, BookingStatus::InProgress, new TransitionContext(ActorType::Provider, $this->provider->id, pinVerified: true, proofsSatisfied: true));
        $this->assertSame(BookingStatus::InProgress, $started->status);
    }

    public function test_a_trip_cannot_complete_without_an_agreed_fare_and_the_required_proofs(): void
    {
        $noFare = $this->bookingIn(BookingStatus::InProgress, ['selected_offer_id' => null]);

        try {
            $this->service()->transition($noFare, BookingStatus::Completed, new TransitionContext(ActorType::Provider, $this->provider->id, proofsSatisfied: true));
            $this->fail('an agreed fare is required');
        } catch (BusinessRuleViolation $exception) {
            $this->assertSame('booking_fare_not_agreed', $exception->errorCode);
        }

        $booking = $this->bookingIn(BookingStatus::InProgress);

        try {
            $this->service()->transition($booking, BookingStatus::Completed, new TransitionContext(ActorType::Provider, $this->provider->id));
            $this->fail('the delivery proof is required');
        } catch (BusinessRuleViolation $exception) {
            $this->assertSame('booking_proof_required', $exception->errorCode);
        }

        $done = $this->service()->transition($booking, BookingStatus::Completed, new TransitionContext(ActorType::Provider, $this->provider->id, proofsSatisfied: true));
        $this->assertNotNull($done->completed_at);
    }

    public function test_cancellation_guards_check_the_actor_and_the_reason(): void
    {
        $booking = $this->bookingIn(BookingStatus::ProviderSelected);

        try {
            $this->service()->transition($booking, BookingStatus::CancelledByCustomer, new TransitionContext(ActorType::Customer, $booking->customer_id + 1));
            $this->fail('another customer must not cancel');
        } catch (ForbiddenActionException $exception) {
            $this->assertSame('booking_not_yours', $exception->errorCode);
        }

        try {
            $this->service()->transition($booking, BookingStatus::CancelledByOperations, new TransitionContext(ActorType::Customer, $booking->customer_id, 'No longer needed'));
            $this->fail('a customer is not operations');
        } catch (ForbiddenActionException $exception) {
            $this->assertSame('booking_operations_only', $exception->errorCode);
        }

        foreach ([new TransitionContext(ActorType::Admin, 1), new TransitionContext(ActorType::Admin, 1, '   ')] as $noReason) {
            try {
                $this->service()->transition($booking, BookingStatus::CancelledByOperations, $noReason);
                $this->fail('operations needs a reason');
            } catch (BusinessRuleViolation $exception) {
                $this->assertSame('booking_cancellation_reason_required', $exception->errorCode);
            }
        }

        try {
            $this->service()->transition($booking, BookingStatus::CancelledByProvider, new TransitionContext(ActorType::Provider, $this->provider->id));
            $this->fail('a provider needs a reason');
        } catch (BusinessRuleViolation $exception) {
            $this->assertSame('booking_cancellation_reason_required', $exception->errorCode);
        }

        $cancelled = $this->service()->transition($booking, BookingStatus::CancelledByCustomer, new TransitionContext(ActorType::Customer, $booking->customer_id, 'Changed my mind'));
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertSame('customer', $cancelled->cancelled_by_type);
        $this->assertSame('Changed my mind', $cancelled->cancellation_reason);
    }

    public function test_searching_again_after_a_provider_cancel_needs_the_policy_to_allow_it(): void
    {
        $booking = $this->bookingIn(BookingStatus::ProviderSelected, ['policy_snapshot' => ['dispatch' => ['search_ttl_seconds' => 300, 'auto_redispatch' => false]]]);

        $this->expectExceptionObject(BusinessRuleViolation::make('booking_redispatch_not_allowed'));
        $this->service()->transition($booking, BookingStatus::SearchingOffers, new TransitionContext(ActorType::Provider, $booking->provider_id));
    }

    public function test_the_setting_independent_ttl_comes_from_the_booking_snapshot_not_from_settings(): void
    {
        app(SettingsService::class)->set(SettingKey::BookingSearchTtlSeconds, 3000);
        $draft = $this->bookingIn(BookingStatus::Draft);

        $moved = $this->service()->transition($draft, BookingStatus::SearchingOffers, TransitionContext::system());

        $this->assertEqualsWithDelta(300, now()->diffInSeconds($moved->search_expires_at, false), 3);
    }

    public function test_the_status_cannot_be_written_outside_the_transition_service(): void
    {
        $booking = $this->bookingIn(BookingStatus::SearchingOffers);

        $this->expectException(LogicException::class);
        $booking->forceFill(['status' => BookingStatus::Completed])->save();
    }

    public function test_the_status_gate_is_closed_again_after_a_write(): void
    {
        $booking = $this->bookingIn(BookingStatus::SearchingOffers);
        BookingStatusGate::run(fn () => $booking->forceFill(['status' => BookingStatus::OffersReceived])->save());

        $this->assertFalse(BookingStatusGate::isOpen());
        $this->expectException(LogicException::class);
        $booking->forceFill(['status' => BookingStatus::Completed])->save();
    }

    public function test_only_the_transition_service_opens_the_status_gate(): void
    {
        $users = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && str_contains((string) file_get_contents($file->getPathname()), 'BookingStatusGate::run(')) {
                $users[] = str_replace(app_path().'/', '', $file->getPathname());
            }
        }

        $users = array_values(array_filter($users, static fn (string $path): bool => ! str_ends_with($path, 'BookingRepositoryInterface.php')));

        $this->assertSame(['Services/Booking/BookingTransitionService.php'], $users);
    }
}
