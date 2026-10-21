<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Identity\UserStatus;
use App\Enums\Settings\SettingKey;
use App\Events\Booking\BookingEnteredSearch;
use App\Models\Booking;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\ZoneServiceCategory;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

/**
 * POST /bookings: the happy path, every validation rule of Plan P2.3 step 4, idempotency, the one-active-booking rule
 * and the guarantee that the client never decides totals, statuses or provider data (Plan I12).
 */
class CreateBookingApiTest extends TestCase
{
    use BuildsBookings;
    use RefreshDatabase;

    private ServiceCategory $category;

    private \App\Models\User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        ['category' => $this->category, 'customer' => $this->customer] = $this->bookingWorld();
        $this->actingAsCustomer($this->customer);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function create(array $overrides = [], ?string $key = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category, $overrides), $this->idempotency($key));
    }

    private function assertRule(\Illuminate\Testing\TestResponse $response, string $code, ?string $field = null): void
    {
        $response->assertStatus(422)->assertJsonPath('errors._code', $code);

        if ($field !== null) {
            $response->assertJsonValidationErrors([$field]);
        }

        $this->assertSame(0, Booking::query()->count(), "[{$code}] must not create a booking");
    }

    public function test_a_customer_creates_a_booking_that_starts_searching(): void
    {
        Event::fake([BookingEnteredSearch::class]);

        $response = $this->create()->assertCreated()
            ->assertJsonPath('data.status', 'searching_offers')
            ->assertJsonPath('data.booking_type', 'on_demand')
            ->assertJsonPath('data.proposed_fare.amount_minor', 5000)
            ->assertJsonPath('data.proposed_fare.currency', 'EGP')
            ->assertJsonPath('data.payment.method', 'cash')
            ->assertJsonPath('data.payment.status', 'pending')
            ->assertJsonPath('data.passengers', 2)
            ->assertJsonPath('data.reference_estimate.available', true)
            ->assertJsonPath('data.allowed_actions', ['update_request'])
            ->assertJsonPath('data.offers.active_count', 0)
            ->assertJsonPath('data.provider', null)
            ->assertJsonPath('meta.replayed', false);

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(BookingStatus::SearchingOffers, $booking->status);
        $this->assertSame($this->customer->id, $booking->customer_id);
        $this->assertNotNull($booking->service_zone_id);
        $this->assertNotNull($booking->fare_policy_id);
        $this->assertNotNull($booking->search_expires_at);
        $this->assertSame(2, $booking->passengers);
        $this->assertSame(1, $booking->luggage_count);
        $this->assertMatchesRegularExpression('/^BK-[A-HJKMNP-Z2-9]{6}$/', $booking->code);
        $this->assertSame($booking->uuid, $response->json('data.id'));
        $this->assertSame(['draft', 'searching_offers'], $booking->statusEvents->map(fn ($event): string => $event->to_status->value)->all());
        $this->assertSame('1 Pickup St', $response->json('data.pickup.address.line'));
        $response->assertJsonMissingPath('data.pin_hash')->assertJsonMissingPath('data.pin_encrypted');
        Event::assertDispatched(BookingEnteredSearch::class, static fn (BookingEnteredSearch $event): bool => $event->bookingId === $booking->id && $event->restart === false);
    }

    public function test_the_reference_estimate_and_snapshots_are_computed_by_the_server(): void
    {
        $this->create()->assertCreated();
        $booking = Booking::query()->firstOrFail();

        $this->assertSame('fake', $booking->route_snapshot['provider']);
        $this->assertGreaterThan(0, $booking->route_snapshot['distance_m']);
        $this->assertSame(true, $booking->reference_estimate['available']);
        $this->assertSame($booking->fare_policy_id, $booking->reference_estimate['fare_policy_id']);
        $this->assertSame($booking->fare_policy_id, $booking->policy_snapshot['fare_policy']['id']);
        $this->assertSame($this->category->id, $booking->service_snapshot['category']['id']);
        $this->assertSame($this->category->publishedVersion->id, $booking->service_category_version_id);
        $this->assertSame(300, $booking->policy_snapshot['dispatch']['search_ttl_seconds']);
        $this->assertSame([1500, 150000], [$booking->policy_snapshot['fare_bounds']['min'], $booking->policy_snapshot['fare_bounds']['max']]);
        $this->assertNotEmpty($booking->service_snapshot['vehicle_categories']);
    }

    public function test_totals_statuses_and_provider_fields_sent_by_the_client_are_ignored(): void
    {
        $this->create([
            'status' => 'completed', 'total_minor' => 1, 'reference_estimate' => ['recommended_minor' => 1], 'provider_id' => 999,
            'selected_offer_id' => 5, 'service_snapshot' => ['x' => 1], 'policy_snapshot' => ['fare_bounds' => ['min' => 1, 'max' => 2]],
            'payment_status' => 'paid', 'customer_id' => 12345, 'code' => 'BK-HACKED', 'search_expires_at' => '2099-01-01T00:00:00Z', 'fare_policy_id' => 77,
        ])->assertCreated();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(BookingStatus::SearchingOffers, $booking->status);
        $this->assertSame(5000, $booking->proposed_fare_minor);
        $this->assertNull($booking->provider_id);
        $this->assertNull($booking->selected_offer_id);
        $this->assertSame('pending', $booking->payment_status->value);
        $this->assertSame($this->customer->id, $booking->customer_id);
        $this->assertNotSame('BK-HACKED', $booking->code);
        $this->assertNotSame(1, $booking->reference_estimate['recommended_minor']);
        $this->assertSame(1500, $booking->policy_snapshot['fare_bounds']['min']);
        $this->assertNotSame(77, $booking->fare_policy_id);
        $this->assertLessThan(now()->addHour()->timestamp, $booking->search_expires_at->timestamp);
    }

    public function test_it_needs_a_signed_in_customer_and_an_idempotency_key(): void
    {
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertStatus(401);

        $this->actingAsCustomer($this->customer);
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category))->assertStatus(422)->assertJsonPath('errors._code', 'idempotency_key_required');
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), ['Idempotency-Key' => 'not-a-uuid'])->assertStatus(422)->assertJsonPath('errors._code', 'idempotency_key_invalid');
        $this->assertSame(0, Booking::query()->count());

        $provider = $this->makeProvider();
        $this->actingAsProvider($provider);
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertForbidden();
        $this->getJson('/api/v1/bookings')->assertForbidden();
    }

    public function test_restricted_and_suspended_accounts_cannot_create_bookings(): void
    {
        $restricted = $this->customer([], UserStatus::Restricted);
        $this->actingAsCustomer($restricted);
        $this->create()->assertStatus(403)->assertJsonPath('errors._code', 'account_restricted');

        $suspended = $this->customer([], UserStatus::Suspended);
        $this->actingAsCustomer($suspended);
        $this->create()->assertStatus(403)->assertJsonPath('errors._code', 'account_suspended');

        $this->assertSame(0, Booking::query()->count());

        // Restricted accounts can still read their history.
        $this->actingAsCustomer($restricted);
        $this->getJson('/api/v1/bookings')->assertOk();
    }

    public function test_the_request_shape_is_validated(): void
    {
        $this->postJson('/api/v1/bookings', [], $this->idempotency())->assertStatus(422)
            ->assertJsonValidationErrors(['service_category_id', 'booking_type', 'pickup', 'proposed_fare_minor']);

        foreach ([
            ['pickup' => ['lat' => 95, 'lng' => 31.25]],
            ['pickup' => ['lat' => 0, 'lng' => 0]],
            ['dropoff' => ['lat' => 30.07]],
            ['stops' => [['lat' => 'x', 'lng' => 1]]],
            ['proposed_fare_minor' => 'abc'],
            ['proposed_fare_minor' => 12.5],
            ['booking_type' => 'teleport'],
            ['payment_method' => 'barter'],
            ['scheduled_at' => 'tomorrow-ish'],
            ['passengers' => 0],
            ['currency' => 'EG'],
        ] as $bad) {
            $this->create($bad)->assertStatus(422);
        }

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_the_pickup_must_be_inside_an_active_zone_that_offers_the_service(): void
    {
        $this->assertRule($this->create(['pickup' => ['lat' => 40.0, 'lng' => 10.0]]), 'outside_service_area', 'pickup');

        ZoneServiceCategory::query()->where('service_category_id', $this->category->id)->update(['active' => false]);
        $this->assertRule($this->create(), 'category_unavailable', 'service_category_id');
    }

    public function test_an_unknown_inactive_or_closed_service_is_unavailable(): void
    {
        $this->assertRule($this->create(['service_category_id' => 999_999]), 'category_unavailable');

        $this->category->forceFill(['active' => false])->save();
        $this->assertRule($this->create(), 'category_unavailable');

        $this->category->forceFill(['active' => true, 'service_hours' => [['day' => (int) now()->addDay()->isoFormat('E'), 'from' => '00:00', 'to' => '23:59']]])->save();
        // Hours are part of the published version only for display; availability reads the category row live.
        $this->assertRule($this->create(), 'category_unavailable');
    }

    public function test_dropoff_rules(): void
    {
        $this->assertRule($this->create(['dropoff' => null]), 'dropoff_required', 'dropoff');
        $this->assertRule($this->create(['dropoff' => ['lat' => 40.0, 'lng' => 10.0]]), 'dropoff_outside_service_area', 'dropoff');
    }

    public function test_the_booking_type_must_be_allowed_by_the_service(): void
    {
        $this->assertRule($this->create(['booking_type' => 'parcel']), 'booking_type_not_allowed', 'booking_type');
    }

    public function test_schedule_rules(): void
    {
        $this->assertRule($this->create(['scheduled_at' => now()->addDay()->toIso8601String()]), 'scheduled_at_not_allowed', 'scheduled_at');
        $this->assertRule($this->create(['booking_type' => 'scheduled']), 'scheduled_at_required', 'scheduled_at');
        $this->assertRule($this->create(['booking_type' => 'scheduled', 'scheduled_at' => now()->addMinutes(5)->toIso8601String()]), 'scheduled_too_soon', 'scheduled_at');
        $this->assertRule($this->create(['booking_type' => 'scheduled', 'scheduled_at' => now()->addDays(40)->toIso8601String()]), 'scheduled_too_far', 'scheduled_at');
    }

    public function test_a_scheduled_booking_waits_in_draft_until_its_search_opens(): void
    {
        Event::fake([BookingEnteredSearch::class]);

        $this->create(['booking_type' => 'scheduled', 'scheduled_at' => now()->addDays(2)->toIso8601String()])->assertCreated()
            ->assertJsonPath('data.status', 'draft')->assertJsonPath('data.search_expires_at', null);

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(BookingStatus::Draft, $booking->status);
        $this->assertNull($booking->search_expires_at);
        Event::assertNotDispatched(BookingEnteredSearch::class);

        // Within the lead time the search opens immediately.
        Booking::query()->delete();
        app(SettingsService::class)->set(SettingKey::ScheduledSearchLeadMinutes, 60);
        $this->create(['booking_type' => 'scheduled', 'scheduled_at' => now()->addMinutes(35)->toIso8601String()])->assertCreated()
            ->assertJsonPath('data.status', 'searching_offers');
    }

    public function test_stops_need_a_service_that_supports_them_and_respect_the_limit(): void
    {
        $stop = ['lat' => 30.06, 'lng' => 31.26];

        $this->create(['stops' => [$stop, $stop]])->assertCreated()->assertJsonCount(2, 'data.stops');
        Booking::query()->delete();

        $this->assertRule($this->create(['stops' => [$stop, $stop, $stop]]), 'too_many_stops', 'stops');

        $other = $this->rideCategory(['supports_stops' => false, 'max_stops' => 0]);
        ZoneServiceCategory::query()->create(['service_zone_id' => ServiceZone::query()->firstOrFail()->id, 'service_category_id' => $other->id, 'active' => true]);
        $this->assertRule($this->postJson('/api/v1/bookings', $this->bookingPayload($other, ['stops' => [$stop]]), $this->idempotency()), 'stops_not_supported', 'stops');
    }

    public function test_passenger_and_capacity_rules(): void
    {
        // The form schema (max 4 passengers) answers first, with the field it belongs to.
        $this->create(['passengers' => 5])->assertStatus(422)->assertJsonValidationErrors(['service_details.passengers']);
        $this->create(['passengers' => null, 'service_details' => []])->assertStatus(422)->assertJsonValidationErrors(['service_details.passengers']);

        // A service whose form does not ask for passengers still needs the count when it limits them.
        $plain = $this->rideCategory([], [$this->schemaField('notes', 'text')]);
        ZoneServiceCategory::query()->create(['service_zone_id' => ServiceZone::query()->firstOrFail()->id, 'service_category_id' => $plain->id, 'active' => true]);
        $this->assertRule($this->postJson('/api/v1/bookings', $this->bookingPayload($plain, ['passengers' => null, 'service_details' => []]), $this->idempotency()), 'passengers_required', 'passengers');
        $this->assertRule($this->create(['passengers' => 2, 'service_details' => ['passengers' => 3]]), 'passengers_mismatch', 'passengers');

        // Capacity of the vehicles behind the service also limits the booking.
        $this->category->vehicleCategories()->update(['passenger_capacity' => 2]);
        $this->category->refresh();
        app(\App\Services\Catalog\ServiceCategoryPublisher::class)->publish($this->category, 'Smaller vehicles', null);
        $this->assertRule($this->create(['passengers' => 3]), 'capacity_exceeded', 'passengers');
    }

    public function test_service_details_are_validated_against_the_published_form_schema(): void
    {
        $this->create(['service_details' => ['luggage' => 1, 'unknown_key' => 'x']])->assertStatus(422)->assertJsonValidationErrors(['service_details.unknown_key']);
        $this->create(['service_details' => ['luggage' => 9]])->assertStatus(422)->assertJsonValidationErrors(['service_details.luggage']);
        $this->create(['service_details' => ['luggage' => 'many']])->assertStatus(422)->assertJsonValidationErrors(['service_details.luggage']);
        $this->assertSame(0, Booking::query()->count());

        $this->create(['service_details' => ['luggage' => 2, 'child_seat' => true, 'notes' => 'Please call']])->assertCreated();
        $booking = Booking::query()->firstOrFail();
        $this->assertEquals(['passengers' => 2, 'luggage' => 2, 'child_seat' => true, 'notes' => 'Please call'], $booking->service_details);
    }

    public function test_the_draft_form_schema_never_applies_only_the_published_version(): void
    {
        $draft = app(\App\Services\Catalog\ServiceFormSchemaService::class)->draftFor($this->category);
        $draft->fields = [$this->schemaField('passengers', 'integer', required: true, min: 1, max: 4), $this->schemaField('extra_required', 'text', required: true)];
        $draft->save();

        $this->create()->assertCreated();
    }

    public function test_fare_bounds_come_from_the_category_and_a_zone_override_wins(): void
    {
        $this->assertRule($this->create(['proposed_fare_minor' => 1499]), 'fare_below_minimum', 'proposed_fare_minor');
        $this->assertRule($this->create(['proposed_fare_minor' => 150001]), 'fare_above_maximum', 'proposed_fare_minor');
        $this->create(['proposed_fare_minor' => 0])->assertStatus(422)->assertJsonValidationErrors(['proposed_fare_minor']);

        $this->create(['proposed_fare_minor' => 1500])->assertCreated();
        Booking::query()->delete();

        ZoneServiceCategory::query()->where('service_category_id', $this->category->id)->update(['overrides' => ['min_fare_minor' => 3000, 'max_fare_minor' => 20000]]);
        $this->assertRule($this->create(['proposed_fare_minor' => 2000]), 'fare_below_minimum');
        $this->assertRule($this->create(['proposed_fare_minor' => 20001]), 'fare_above_maximum');
        $this->create(['proposed_fare_minor' => 3000])->assertCreated();
        $this->assertSame('zone', Booking::query()->firstOrFail()->policy_snapshot['fare_bounds']['source']);
    }

    public function test_the_platform_ceiling_applies_when_nothing_is_configured(): void
    {
        $this->category->forceFill(['min_customer_fare_minor' => null, 'max_customer_fare_minor' => null])->save();
        app(\App\Services\Catalog\ServiceCategoryPublisher::class)->publish($this->category, 'No bounds', null);

        $this->assertRule($this->create(['proposed_fare_minor' => (int) config('booking.hard_max_fare_minor') + 1]), 'fare_above_maximum');
        $this->create(['proposed_fare_minor' => 100])->assertCreated();
    }

    public function test_only_the_market_currency_is_accepted(): void
    {
        $this->assertRule($this->create(['currency' => 'USD']), 'currency_mismatch', 'currency');
        $this->create(['currency' => 'egp'])->assertCreated();
    }

    public function test_cash_is_the_only_payment_method_until_the_gateway_exists(): void
    {
        $this->assertRule($this->create(['payment_method' => 'card']), 'payment_method_unavailable', 'payment_method');
        $this->create(['payment_method' => null])->assertCreated()->assertJsonPath('data.payment.method', 'cash');
        Booking::query()->delete();

        $this->category->forceFill(['cash_allowed' => false])->save();
        app(\App\Services\Catalog\ServiceCategoryPublisher::class)->publish($this->category, 'No cash', null);
        $this->assertRule($this->create(), 'payment_method_unavailable', 'payment_method');
    }

    public function test_a_maps_outage_is_an_error_and_creates_nothing(): void
    {
        config(['integrations.maps.driver' => null]);
        $this->app->forgetInstance(\App\Integrations\Maps\Contracts\MapsProvider::class);

        $this->create()->assertStatus(503)->assertJsonPath('errors._code', 'maps_unavailable');
        $this->assertSame(0, Booking::query()->count());
    }

    public function test_one_active_on_demand_booking_per_customer_unless_the_setting_allows_more(): void
    {
        $this->create()->assertCreated();
        $this->create()->assertStatus(409)->assertJsonPath('errors._code', 'customer_has_active_booking');
        $this->assertSame(1, Booking::query()->count());

        // Scheduled bookings do not count against the on-demand limit.
        $this->create(['booking_type' => 'scheduled', 'scheduled_at' => now()->addDays(3)->toIso8601String()])->assertCreated();

        app(SettingsService::class)->set(SettingKey::MaxActiveOnDemandBookings, 2);
        $this->create()->assertCreated();
        $this->create()->assertStatus(409);
        $this->assertSame(3, Booking::query()->count());
    }

    public function test_a_finished_booking_frees_the_slot(): void
    {
        $this->create()->assertCreated();
        $booking = Booking::query()->firstOrFail();
        \App\Support\Booking\BookingStatusGate::run(fn () => $booking->forceFill(['status' => BookingStatus::CancelledByCustomer])->save());

        $this->create()->assertCreated();
        $this->assertSame(BookingType::OnDemand, Booking::query()->latest('id')->firstOrFail()->booking_type);
    }

    public function test_the_same_idempotency_key_creates_one_booking_and_replays_the_answer(): void
    {
        $key = (string) \Illuminate\Support\Str::uuid();

        $first = $this->create([], $key)->assertCreated();
        $second = $this->create([], $key)->assertCreated()->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->json('data.code'), $second->json('data.code'));
        $this->assertSame(1, Booking::query()->count());
        $this->assertSame(2, \App\Models\BookingStatusEvent::query()->count());
    }

    public function test_the_same_key_with_another_payload_is_refused(): void
    {
        $key = (string) \Illuminate\Support\Str::uuid();
        $this->create([], $key)->assertCreated();

        $this->create(['proposed_fare_minor' => 6000], $key)->assertStatus(422)->assertJsonPath('errors._code', 'idempotency_key_reused');
        $this->assertSame(1, Booking::query()->count());
        $this->assertSame(5000, Booking::query()->firstOrFail()->proposed_fare_minor);
    }

    public function test_the_key_is_also_enforced_by_the_database_when_the_stored_response_is_gone(): void
    {
        $key = (string) \Illuminate\Support\Str::uuid();
        $first = $this->create([], $key)->assertCreated();

        DB::table('idempotency_keys')->delete();
        $again = $this->create([], $key)->assertCreated()->assertJsonPath('meta.replayed', true);

        $this->assertSame($first->json('data.id'), $again->json('data.id'));
        $this->assertSame(1, Booking::query()->count());
        $this->assertSame(1, DB::table('bookings')->where('idempotency_key', $key)->count());
    }

    public function test_two_customers_never_share_a_key(): void
    {
        $key = (string) \Illuminate\Support\Str::uuid();
        $this->create([], $key)->assertCreated();

        $other = $this->customer();
        $this->actingAsCustomer($other);
        $this->create([], $key)->assertCreated();

        $this->assertSame(2, Booking::query()->count());
        $this->assertSame(2, Booking::query()->distinct()->count('customer_id'));
    }

    public function test_every_booking_code_is_unique(): void
    {
        app(SettingsService::class)->set(SettingKey::MaxActiveOnDemandBookings, 10);

        foreach (range(1, 5) as $ignored) {
            $this->create()->assertCreated();
        }

        $this->assertSame(5, Booking::query()->distinct()->count('code'));
        $this->assertSame(5, Booking::query()->distinct()->count('uuid'));
    }
}
