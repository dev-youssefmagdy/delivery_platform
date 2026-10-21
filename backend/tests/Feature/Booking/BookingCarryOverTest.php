<?php

namespace Tests\Feature\Booking;

use App\Enums\Admin\AdminPermission;
use App\Enums\Booking\BookingStatus;
use App\Enums\Common\AuditAction;
use App\Enums\Geo\ZoneStatus;
use App\Enums\Identity\UserStatus;
use App\Models\Booking;
use App\Models\BookingStop;
use App\Models\DispatchRecipient;
use App\Models\ProviderLocation;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\User;
use App\Policies\RealtimeChannelPolicy;
use App\Repositories\Contracts\Dispatch\BookingParticipationRepositoryInterface;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Services\Booking\DispatchableBookingSource;
use App\Services\Dispatch\DispatchService;
use App\Support\Geo\GeoPoint;
use Database\Factories\ServiceZoneFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsBookings;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * The seams P2.2 / P1.x left for bookings, now wired against the real schema (ADR-66, ADR-84, ADR-86, ADR-89, ADR-93):
 * dispatch source and foreign keys, participation, workload, deletion guards and scrub, deactivation guards, the
 * Customers admin filter and tab.
 */
class BookingCarryOverTest extends TestCase
{
    use BuildsBookings;
    use InteractsWithAdmin;
    use RefreshDatabase;

    private ServiceCategory $category;

    private ServiceZone $zone;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        ['category' => $this->category, 'zone' => $this->zone, 'customer' => $this->customer] = $this->bookingWorld();
    }

    private function booking(BookingStatus $status = BookingStatus::SearchingOffers, array $state = []): Booking
    {
        return Booking::factory()->forCustomer($this->customer)->forCategory($this->category)->inZone($this->zone)->status($status)->state($state)->create();
    }

    // ---- dispatch ---------------------------------------------------------------------------------------------------

    public function test_dispatch_reads_the_booking_and_shows_providers_only_the_staged_summary(): void
    {
        Event::fake([\App\Events\Realtime\BookingRequested::class]);
        ['category' => $category] = ['category' => $this->category];
        $provider = $this->onlineProviderFor($category);
        $booking = $this->booking(state: ['pickup_address' => ['line' => '1 Secret St'], 'dropoff_address' => ['line' => '2 Secret Ave'], 'service_details' => ['notes' => 'Ring the bell, ask for Mona']]);

        $request = app(DispatchableBookingSource::class)->find($booking->id);
        $this->assertTrue($request?->searching);
        $this->assertSame($booking->uuid, $request?->bookingUuid);
        $this->assertSame($booking->request_version, $request?->generation);

        $result = app(DispatchService::class)->runWave($request, 1);

        $this->assertSame(1, $result->notified);
        $this->assertSame(1, DispatchRecipient::query()->where('booking_id', $booking->id)->where('provider_profile_id', $provider->id)->count());
        Event::assertDispatched(\App\Events\Realtime\BookingRequested::class, function ($event) use ($booking): bool {
            $payload = json_encode($event->payload);

            return $event->bookingUuid === $booking->uuid
                && $event->payload['request']['fare']['amount_minor'] === 5000
                && ! str_contains((string) $payload, 'Secret')
                && ! str_contains((string) $payload, 'Mona')
                && ! str_contains((string) $payload, (string) $this->customer->phone_e164)
                && ! array_key_exists('customer', $event->payload['request'])
                && ! array_key_exists('pickup', $event->payload['request'])
                && $event->payload['pickup_area']['lat'] === 30.05;
        });
    }

    public function test_the_dispatch_tables_reference_bookings(): void
    {
        $provider = $this->makeProvider();

        $this->expectException(QueryException::class);
        DispatchRecipient::query()->create(['booking_id' => 987_654, 'provider_profile_id' => $provider->id, 'wave' => 1, 'distance_m' => 1, 'notified_at' => now()]);
    }

    public function test_location_history_references_bookings_and_keeps_its_rows_when_a_booking_goes(): void
    {
        $provider = $this->makeProvider();

        try {
            ProviderLocation::query()->create(['provider_profile_id' => $provider->id, 'point' => GeoPoint::make(30.05, 31.25), 'source' => 'gps', 'booking_id' => 987_654, 'recorded_at' => now()]);
            $this->fail('an unknown booking must be refused');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $booking = $this->booking();
        $row = ProviderLocation::query()->create(['provider_profile_id' => $provider->id, 'point' => GeoPoint::make(30.05, 31.25), 'source' => 'gps', 'booking_id' => $booking->id, 'recorded_at' => now()]);
        DB::table('bookings')->where('id', $booking->id)->delete();

        $this->assertNull($row->fresh()->booking_id);
    }

    public function test_participation_is_decided_by_the_real_booking_schema(): void
    {
        $repository = app(BookingParticipationRepositoryInterface::class);
        $booking = $this->booking();
        $dispatched = $this->makeProvider();
        $selected = $this->makeProvider();
        $outsider = $this->makeProvider();
        DispatchRecipient::query()->create(['booking_id' => $booking->id, 'provider_profile_id' => $dispatched->id, 'wave' => 1, 'distance_m' => 100, 'notified_at' => now()]);
        $booking->forceFill(['provider_id' => $selected->id])->save();

        $this->assertTrue($repository->isCustomerOf($booking->uuid, $this->customer->id));
        $this->assertFalse($repository->isCustomerOf($booking->uuid, $this->customer->id + 1));
        $this->assertFalse($repository->isCustomerOf('00000000-0000-4000-8000-000000000000', $this->customer->id));
        $this->assertTrue($repository->isProviderOf($booking->uuid, $dispatched->id));
        $this->assertTrue($repository->isProviderOf($booking->uuid, $selected->id));
        $this->assertFalse($repository->isProviderOf($booking->uuid, $outsider->id));
        $this->assertFalse($repository->isProviderOf('00000000-0000-4000-8000-000000000000', $dispatched->id));
    }

    public function test_the_booking_channel_admits_its_customer_and_its_providers_only(): void
    {
        $policy = app(RealtimeChannelPolicy::class);
        $booking = $this->booking();
        $dispatched = $this->makeProvider();
        $outsider = $this->makeProvider();
        DispatchRecipient::query()->create(['booking_id' => $booking->id, 'provider_profile_id' => $dispatched->id, 'wave' => 1, 'distance_m' => 100, 'notified_at' => now()]);
        $as = static fn (User $user, string $ability): User => $user->refresh()->withAccessToken(new PersonalAccessToken(['name' => 'test', 'abilities' => [$ability]]));

        $this->assertTrue($policy->joinBooking($as($this->customer, 'customer'), $booking->uuid));
        $this->assertFalse($policy->joinBooking($as($this->customer(), 'customer'), $booking->uuid));
        $this->assertTrue($policy->joinBooking($as($dispatched->user, 'provider'), $booking->uuid));
        $this->assertFalse($policy->joinBooking($as($outsider->user, 'provider'), $booking->uuid));
    }

    public function test_the_workload_reads_assigned_bookings_and_the_money_seam_is_neutral_until_the_ledger_exists(): void
    {
        $provider = $this->makeProvider();
        $finances = app(FinancialPositionRepositoryInterface::class);

        $this->assertSame(0, $finances->cashDebtMinor($provider));
        $this->assertSame(0, $finances->openItemsForProvider($provider));
        $this->assertSame(0, $finances->openItemsForCustomer($this->customer));
        $this->assertInstanceOf(\App\Repositories\Eloquent\Finance\PreLedgerFinancialPositionRepository::class, $finances);
    }

    // ---- deletion ---------------------------------------------------------------------------------------------------

    public function test_a_customer_with_a_booking_in_flight_cannot_be_anonymised(): void
    {
        $request = $this->pendingDeletion($this->customer);
        $this->booking();
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);
        $url = route('admin.customers.deletion-requests.approve', [$this->customer, $request->uuid]);

        $this->postJson($url, ['reason' => 'Identity verified'])->assertStatus(422)->assertJsonPath('errors._code', 'customer_has_active_bookings');
        $this->assertNotNull($this->customer->refresh()->phone_e164);
    }

    public function test_anonymising_a_customer_scrubs_the_personal_data_inside_their_bookings(): void
    {
        Storage::fake('local');
        $done = $this->booking(BookingStatus::Completed, [
            'pickup_address' => ['title' => 'Home', 'line' => '1 Secret St'],
            'dropoff_address' => ['line' => '2 Secret Ave'],
            'service_details' => ['notes' => 'Ask for Mona', 'passengers' => 2],
            'pickup_point' => GeoPoint::make(30.0512345, 31.2587654),
            'dropoff_point' => GeoPoint::make(30.0712345, 31.2787654),
            'route_snapshot' => ['distance_m' => 5200, 'duration_s' => 900, 'polyline' => 'abcdef', 'provider' => 'fake'],
            'cancellation_reason' => null,
        ]);
        $stop = BookingStop::query()->create(['booking_id' => $done->id, 'position' => 1, 'point' => GeoPoint::make(30.0612345, 31.2687654), 'address' => ['line' => 'Stop street'], 'metadata' => ['contact' => 'x']]);
        $untouched = Booking::factory()->forCategory($this->category)->status(BookingStatus::Completed)->create(['pickup_address' => ['line' => 'Another customer street']]);
        $request = $this->pendingDeletion($this->customer);
        $admin = $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::CustomersProcessDeletion]);

        $this->postJson(route('admin.customers.deletion-requests.approve', [$this->customer, $request->uuid]), ['reason' => 'Identity verified'])->assertOk();

        $fresh = $done->fresh();
        $this->assertNull($fresh->pickup_address);
        $this->assertNull($fresh->dropoff_address);
        $this->assertSame([], $fresh->service_details);
        $this->assertSame(30.05, round($fresh->pickup_point->lat, 2));
        $this->assertSame(30.05, $fresh->pickup_point->lat, 'coordinates are cut to two decimals (about 1 km)');
        $this->assertSame(31.26, $fresh->pickup_point->lng);
        $this->assertSame(30.07, $fresh->dropoff_point?->lat);
        $this->assertArrayNotHasKey('polyline', $fresh->route_snapshot);
        $this->assertSame(5200, $fresh->route_snapshot['distance_m']);
        $this->assertNull($stop->fresh()->address);
        $this->assertNull($stop->fresh()->metadata);
        $this->assertSame(30.06, $stop->fresh()->point->lat);

        // What the money records point at stays.
        $this->assertSame($done->code, $fresh->code);
        $this->assertSame(5000, $fresh->proposed_fare_minor);
        $this->assertSame(BookingStatus::Completed, $fresh->status);
        $this->assertSame($this->customer->id, $fresh->customer_id);
        $this->assertSame('Another customer street', $untouched->fresh()->pickup_address['line']);

        $log = \App\Models\AdminAuditLog::query()->where('action', AuditAction::Anonymised->value)->sole();
        $this->assertSame(1, $log->after['bookings_scrubbed'] ?? $log->meta['bookings_scrubbed'] ?? null);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame(UserStatus::Deleted, $this->customer->refresh()->status);
    }

    public function test_a_provider_with_an_assigned_booking_cannot_be_deleted(): void
    {
        $provider = $this->approvedProviderFor($this->category);
        $this->booking(BookingStatus::ProviderHeadingToPickup, ['provider_id' => $provider->id]);
        $request = \App\Models\AccountDeletionRequest::query()->create(['user_id' => $provider->user_id, 'status' => 'pending', 'previous_user_status' => UserStatus::Active, 'previous_provider_status' => 'approved', 'reason' => 'Leaving', 'requested_at' => now()]);
        $provider->user->forceFill(['status' => UserStatus::DeletionPending])->save();
        $provider->forceFill(['status' => \App\Enums\Provider\ProviderStatus::DeletionPending])->save();
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::CustomersProcessDeletion]);

        $this->postJson(route('admin.providers.deletion-requests.approve', [$provider, $request->uuid]), ['reason' => 'Retention checked'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'provider_has_active_jobs');
    }

    // ---- deactivation guards ----------------------------------------------------------------------------------------

    public function test_a_category_with_bookings_in_flight_needs_an_explicit_confirmation_to_deactivate(): void
    {
        $other = $this->rideCategory();
        $this->booking();
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $url = route('admin.service-categories.deactivate', $this->category);

        $this->postJson($url, ['reason' => 'Retiring the service'])->assertStatus(422)->assertJsonPath('errors._code', 'category_has_active_bookings');
        $this->assertTrue($this->category->refresh()->active);

        $this->postJson($url, ['reason' => 'Retiring the service', 'confirm_active_bookings' => '1'])->assertOk();
        $this->assertFalse($this->category->refresh()->active);
        $this->assertTrue($other->refresh()->active);
        $log = \App\Models\AdminAuditLog::query()->where('action', AuditAction::StatusChanged->value)->latest('id')->firstOrFail();
        $this->assertSame(1, $log->meta['active_bookings']);
    }

    public function test_finished_bookings_do_not_block_a_category_deactivation(): void
    {
        $this->rideCategory();
        $this->booking(BookingStatus::Completed);
        $this->booking(BookingStatus::CancelledByCustomer);
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->postJson(route('admin.service-categories.deactivate', $this->category), ['reason' => 'Retiring the service'])->assertOk();
    }

    public function test_suspending_a_zone_with_bookings_in_flight_needs_an_explicit_confirmation(): void
    {
        $this->booking();
        $admin = $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $url = route('admin.service-zones.suspend', $this->zone);

        $this->postJson($url, ['reason' => 'Road works'])->assertStatus(422)->assertJsonPath('errors._code', 'zone_has_active_bookings')->assertJsonValidationErrors(['confirm_active_bookings']);
        $this->assertSame(ZoneStatus::Active, $this->zone->refresh()->status);

        $this->postJson($url, ['reason' => 'Road works', 'confirm_active_bookings' => '1'])->assertOk();
        $this->assertSame(ZoneStatus::Suspended, $this->zone->refresh()->status);
        $log = \App\Models\AdminAuditLog::query()->where('action', AuditAction::StatusChanged->value)->where('auditable_type', 'service_zone')->firstOrFail();
        $this->assertSame([1, true, $admin->id], [$log->meta['active_bookings'], $log->meta['confirmed_active_bookings'], $log->actor_id]);
    }

    public function test_a_zone_without_bookings_in_flight_suspends_without_confirmation_and_the_page_shows_the_count(): void
    {
        $this->booking(BookingStatus::Completed);
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);

        $this->get(route('admin.service-zones.show', $this->zone))->assertOk()->assertSee('data-modal-open="suspend-zone"', false)->assertDontSee('name="confirm_active_bookings"', false);
        $this->postJson(route('admin.service-zones.suspend', $this->zone), ['reason' => 'Road works'])->assertOk();

        $other = ServiceZone::factory()->create(['polygon' => ServiceZoneFactory::square(31.0, 31.2, 0.1)]);
        $this->booking(state: ['service_zone_id' => $other->id]);
        $this->get(route('admin.service-zones.show', $other))->assertOk()->assertSee('name="confirm_active_bookings"', false);
    }

    public function test_a_suspended_zone_with_bookings_in_flight_cannot_be_deleted(): void
    {
        $this->booking();
        $this->zone->forceFill(['status' => ZoneStatus::Suspended])->save();
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);

        $this->deleteJson(route('admin.service-zones.destroy', $this->zone), ['reason' => 'Removing the area'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'zone_has_active_bookings_cannot_delete');
        $this->assertModelExists($this->zone);
    }

    // ---- Customers admin --------------------------------------------------------------------------------------------

    public function test_the_customers_listing_filters_by_active_booking(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $this->booking();
        $idle = $this->customer();
        $finished = $this->customer();
        Booking::factory()->forCustomer($finished)->forCategory($this->category)->status(BookingStatus::Completed)->create();
        $url = route('admin.customers.index');

        $with = $this->datatable($url, ['has_active_booking' => '1'])->assertJsonPath('recordsFiltered', 1);
        $this->assertSame($this->customer->id, $with->json('data.0.id'));
        $without = $this->datatable($url, ['has_active_booking' => '0'])->assertJsonPath('recordsFiltered', 2);
        $this->assertEqualsCanonicalizing([$idle->id, $finished->id], array_column($without->json('data'), 'id'));
        $this->datatable($url)->assertJsonPath('recordsFiltered', 3);
    }

    public function test_the_bookings_tab_lists_only_that_customers_bookings(): void
    {
        $this->actingAsAdmin([AdminPermission::CustomersView]);
        $mine = $this->booking();
        $this->booking(BookingStatus::Completed);
        Booking::factory()->forCategory($this->category)->create();
        $url = route('admin.customers.tabs.bookings', $this->customer);

        $this->get($url)->assertOk()->assertSee('id="customer-bookings-table"', false);
        $json = $this->datatable($url, [], ['order' => [['column' => 7, 'dir' => 'desc']]])->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->assertContains(strip_tags($mine->code), array_map('strip_tags', array_column($json->json('data'), 'code')));
        $this->assertStringContainsString('50', (string) $json->json('data.0.proposed_fare_minor'));
        $this->assertStringNotContainsString('Pickup street', (string) json_encode($json->json()));

        $this->actingAsAdmin([AdminPermission::DriversView]);
        $this->getJson($url)->assertForbidden();
    }
}
