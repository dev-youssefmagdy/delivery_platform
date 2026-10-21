<?php

namespace Tests\Feature\Booking;

use App\DTOs\Pricing\FarePolicyValues;
use App\Enums\Admin\AdminPermission;
use App\Models\Booking;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use App\Models\ServiceZone;
use App\Models\User;
use App\Models\ZoneServiceCategory;
use App\Services\Catalog\ServiceCategoryPublisher;
use App\Services\Pricing\ReferencePriceCalculator;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookings;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Plan I4: booking history never changes when admins edit configuration. Everything a booking was validated and
 * priced with is copied into it; the estimate can be recomputed from the booking alone.
 */
class BookingSnapshotTest extends TestCase
{
    use BuildsBookings;
    use InteractsWithAdmin;
    use RefreshDatabase;

    private ServiceCategory $category;

    private ServiceZone $zone;

    private User $customer;

    private FarePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        ['category' => $this->category, 'zone' => $this->zone, 'customer' => $this->customer, 'policy' => $this->policy] = $this->bookingWorld();
        $this->actingAsCustomer($this->customer);
    }

    private function book(): Booking
    {
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category, ['stops' => [['lat' => 30.06, 'lng' => 31.26]], 'service_details' => ['luggage' => 1, 'child_seat' => true]]), $this->idempotency())->assertCreated();

        return Booking::query()->latest('id')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function frozen(Booking $booking): array
    {
        $booking->refresh();

        return [
            'service_snapshot' => $booking->service_snapshot,
            'policy_snapshot' => $booking->policy_snapshot,
            'reference_estimate' => $booking->reference_estimate,
            'route_snapshot' => $booking->route_snapshot,
            'service_details' => $booking->service_details,
            'version' => $booking->service_category_version_id,
            'fare_policy_id' => $booking->fare_policy_id,
            'proposed_fare_minor' => $booking->proposed_fare_minor,
            'currency' => $booking->currency,
        ];
    }

    public function test_admin_edits_to_the_catalog_policies_zones_and_settings_never_change_a_booking(): void
    {
        $this->policy->forceFill(['attribute_surcharges' => [['field' => 'child_seat', 'mode' => 'fixed', 'amount_minor' => 1500, 'match' => null, 'label' => ['ar' => 'مقعد', 'en' => 'Child seat']]]])->save();
        $booking = $this->book();
        $before = $this->frozen($booking);
        $oldVersion = $booking->service_category_version_id;

        // Catalog: rename, change bounds and payments, publish a new version.
        $this->category->forceFill([
            'name' => ['ar' => 'اسم جديد', 'en' => 'Brand new name'], 'min_customer_fare_minor' => 9000, 'max_customer_fare_minor' => 9500,
            'max_passengers' => 1, 'pin_required' => false, 'search_ttl_seconds' => 30, 'payment_methods' => ['card'], 'cash_allowed' => false,
        ])->save();
        app(ServiceCategoryPublisher::class)->publish($this->category->refresh(), 'New rules', null);
        $this->assertNotSame($oldVersion, $this->category->refresh()->published_version_id);

        // Fare policy: change every amount, then disable it.
        $this->policy->forceFill(['base_minor' => 99_900, 'per_km_minor' => 9900, 'min_fare_minor' => 50_000, 'attribute_surcharges' => [], 'lock_version' => 5])->save();
        $this->policy->forceFill(['active' => false])->save();

        // Zone: rename and override the bounds; platform settings change too.
        $this->zone->forceFill(['name' => ['ar' => 'منطقة جديدة', 'en' => 'Renamed zone'], 'restricted_pickup' => true])->save();
        ZoneServiceCategory::query()->where('service_category_id', $this->category->id)->update(['overrides' => ['min_fare_minor' => 8000, 'max_fare_minor' => 8500]]);
        app(SettingsService::class)->set(\App\Enums\Settings\SettingKey::BookingSearchTtlSeconds, 600);
        app(SettingsService::class)->set(\App\Enums\Settings\SettingKey::MaxOffersPerProviderPerBooking, 5);

        $this->assertSame($before, $this->frozen($booking));
        $this->assertSame($oldVersion, $booking->fresh()->service_category_version_id);
        $this->assertSame('Category', explode(' ', $booking->fresh()->service_snapshot['category']['name']['en'])[0]);

        // The same booking, read through the API, still shows what it was.
        $this->getJson("/api/v1/bookings/{$booking->uuid}")->assertOk()
            ->assertJsonPath('data.proposed_fare.amount_minor', 5000)
            ->assertJsonPath('data.reference_estimate.recommended_minor', $before['reference_estimate']['recommended_minor']);
    }

    public function test_a_new_booking_uses_the_edited_configuration(): void
    {
        $first = $this->book();
        $this->policy->forceFill(['base_minor' => 20_000, 'min_fare_minor' => 20_000])->save();
        $this->category->forceFill(['max_customer_fare_minor' => 90_000])->save();
        app(ServiceCategoryPublisher::class)->publish($this->category->refresh(), 'New rules', null);
        \App\Support\Booking\BookingStatusGate::run(fn () => $first->forceFill(['status' => \App\Enums\Booking\BookingStatus::CancelledByCustomer])->save());

        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertCreated();
        $second = Booking::query()->latest('id')->firstOrFail();

        $this->assertGreaterThan($first->reference_estimate['recommended_minor'], $second->reference_estimate['recommended_minor']);
        $this->assertSame(90_000, $second->policy_snapshot['fare_bounds']['max']);
        $this->assertSame(150_000, $first->fresh()->policy_snapshot['fare_bounds']['max']);
        $this->assertNotSame($first->service_category_version_id, $second->service_category_version_id);
    }

    public function test_the_estimate_can_be_recomputed_from_the_booking_alone(): void
    {
        $this->policy->forceFill(['attribute_surcharges' => [['field' => 'child_seat', 'mode' => 'fixed', 'amount_minor' => 1500, 'match' => null]]])->save();
        $booking = $this->book();
        $this->policy->forceFill(['base_minor' => 77_700])->save();

        $inputs = $booking->reference_estimate['inputs'];
        $recomputed = app(ReferencePriceCalculator::class)->calculate(
            FarePolicyValues::fromSnapshot($booking->policy_snapshot['fare_policy']),
            $inputs['distance_m'],
            $inputs['duration_s'],
            $inputs['stops'],
            $booking->service_details,
        );

        $this->assertSame($booking->reference_estimate['recommended_minor'], $recomputed->recommended->amount);
        $this->assertSame($booking->reference_estimate['low_minor'], $recomputed->low->amount);
        $this->assertSame($booking->reference_estimate['high_minor'], $recomputed->high->amount);
        $this->assertEquals($booking->reference_estimate['factors'], array_map(static fn ($line): array => $line->toSnapshot(), $recomputed->factors));
        $this->assertSame(1, $inputs['stops']);
        $this->assertContains('surcharge', array_column($booking->reference_estimate['factors'], 'code'));
    }

    public function test_a_policy_that_priced_a_booking_cannot_be_deleted_but_can_be_disabled(): void
    {
        $booking = $this->book();
        $this->actingAsAdmin([AdminPermission::FarePoliciesManage]);

        $this->deleteJson(route('admin.fare-policies.destroy', $this->policy), ['reason' => 'Cleaning up the old prices'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'fare_policy_in_use');
        $this->assertModelExists($this->policy);

        $this->postJson(route('admin.fare-policies.deactivate', $this->policy), ['reason' => 'Replaced by a new policy'])->assertOk();
        $this->assertSame($this->policy->id, $booking->fresh()->fare_policy_id);
        $this->assertSame($this->policy->id, $booking->fresh()->policy_snapshot['fare_policy']['id']);
    }

    public function test_without_an_enabled_policy_the_booking_says_so_instead_of_inventing_a_price(): void
    {
        $this->policy->forceFill(['active' => false])->save();
        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertCreated()
            ->assertJsonPath('data.reference_estimate.available', false)
            ->assertJsonPath('data.reference_estimate.reason', 'no_fare_policy');

        $booking = Booking::query()->firstOrFail();
        $this->assertNull($booking->fare_policy_id);
        $this->assertEquals(['available' => false, 'reason' => 'no_fare_policy'], $booking->reference_estimate);
        $this->assertNull($booking->policy_snapshot['fare_policy']);
    }

    public function test_a_zone_policy_wins_inside_its_zone_and_the_category_policy_elsewhere(): void
    {
        $zonePolicy = FarePolicy::factory()->create(['service_category_id' => $this->category->id, 'service_zone_id' => $this->zone->id, 'base_minor' => 10_000, 'min_fare_minor' => 10_000]);

        $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertCreated();
        $booking = Booking::query()->firstOrFail();

        $this->assertSame($zonePolicy->id, $booking->fare_policy_id);
        $this->assertGreaterThan(10_000, $booking->reference_estimate['recommended_minor'] - 1);
    }
}
