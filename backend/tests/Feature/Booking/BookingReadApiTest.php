<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\BookingStatus;
use App\Models\Booking;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

/**
 * GET /bookings and GET /bookings/{booking}: scoped to the owner (IDOR), filters, pagination and the shape of the
 * customer view.
 */
class BookingReadApiTest extends TestCase
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

    public function test_customer_b_cannot_read_customer_as_booking_in_any_way(): void
    {
        $this->actingAsCustomer($this->customer);
        $created = $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category), $this->idempotency())->assertCreated();
        $uuid = $created->json('data.id');
        $this->getJson("/api/v1/bookings/{$uuid}")->assertOk();

        $other = $this->customer();
        $this->actingAsCustomer($other);

        $this->getJson("/api/v1/bookings/{$uuid}")->assertNotFound();
        $this->patchJson("/api/v1/bookings/{$uuid}/request", ['proposed_fare_minor' => 9000], $this->idempotency())->assertNotFound();
        $this->getJson('/api/v1/bookings')->assertOk()->assertJsonCount(0, 'data');

        // A foreign uuid answers exactly like a missing one: no existence oracle.
        $missing = $this->getJson('/api/v1/bookings/'.\Illuminate\Support\Str::uuid())->assertNotFound();
        $this->assertSame($missing->json('errors._code') ?? null, $this->getJson("/api/v1/bookings/{$uuid}")->json('errors._code') ?? null);
        $this->assertSame(5000, Booking::query()->where('uuid', $uuid)->firstOrFail()->proposed_fare_minor);
    }

    public function test_the_detail_view_has_the_server_computed_parts(): void
    {
        $this->actingAsCustomer($this->customer);
        $uuid = $this->postJson('/api/v1/bookings', $this->bookingPayload($this->category, ['stops' => [['lat' => 30.06, 'lng' => 31.26]]]), $this->idempotency())->json('data.id');

        $this->getJson("/api/v1/bookings/{$uuid}")->assertOk()
            ->assertJsonPath('data.id', $uuid)
            ->assertJsonPath('data.status', 'searching_offers')
            ->assertJsonPath('data.status_label', BookingStatus::SearchingOffers->label())
            ->assertJsonPath('data.service.id', $this->category->id)
            ->assertJsonPath('data.pickup.address.title', 'Home')
            ->assertJsonPath('data.dropoff.address.line', '2 Dropoff St')
            ->assertJsonPath('data.stops.0.position', 1)
            ->assertJsonPath('data.reference_estimate.available', true)
            ->assertJsonPath('data.allowed_actions', ['update_request'])
            ->assertJsonPath('data.timeline.0.status', 'draft')
            ->assertJsonPath('data.timeline.1.status', 'searching_offers')
            ->assertJsonPath('data.timeline.1.previous_status', 'draft')
            ->assertJsonPath('data.offers.active_count', 0)
            ->assertJsonPath('data.provider', null)
            ->assertJsonStructure(['data' => ['route' => ['distance_m', 'duration_s', 'polyline'], 'search_expires_at', 'request_version', 'created_at', 'reference_estimate' => ['low_minor', 'recommended_minor', 'high_minor', 'factors' => [['code', 'label', 'amount_minor']], 'notice']]])
            ->assertJsonMissingPath('data.pin_hash')
            ->assertJsonMissingPath('data.policy_snapshot')
            ->assertJsonMissingPath('data.customer_id');
    }

    public function test_allowed_actions_are_empty_once_the_search_phase_is_over(): void
    {
        $this->actingAsCustomer($this->customer);
        $booking = Booking::factory()->forCustomer($this->customer)->forCategory($this->category)->status(BookingStatus::Completed)->create();

        $this->getJson("/api/v1/bookings/{$booking->uuid}")->assertOk()->assertJsonPath('data.allowed_actions', []);
    }

    public function test_the_timeline_hides_internal_reasons(): void
    {
        $this->actingAsCustomer($this->customer);
        $booking = Booking::factory()->forCustomer($this->customer)->forCategory($this->category)->create();
        foreach ([['operations', 'Fraud ring #12'], ['customer', 'Changed my mind'], ['system', 'search_expired']] as [$actor, $reason]) {
            \App\Models\BookingStatusEvent::query()->create(['booking_id' => $booking->id, 'from_status' => null, 'to_status' => BookingStatus::SearchingOffers, 'actor_type' => $actor, 'reason' => $reason, 'sequence' => random_int(1, 10_000)]);
        }

        $timeline = collect($this->getJson("/api/v1/bookings/{$booking->uuid}")->json('data.timeline'));

        $this->assertNull($timeline->firstWhere('actor', 'operations')['reason']);
        $this->assertSame('Changed my mind', $timeline->firstWhere('actor', 'customer')['reason']);
        $this->assertSame('search_expired', $timeline->firstWhere('actor', 'system')['reason']);
    }

    public function test_history_is_paginated_newest_first_and_filterable(): void
    {
        $this->actingAsCustomer($this->customer);
        $other = $this->rideCategory();
        $old = Booking::factory()->forCustomer($this->customer)->forCategory($this->category)->status(BookingStatus::Completed)->create(['created_at' => now()->subDays(10)]);
        $mid = Booking::factory()->forCustomer($this->customer)->forCategory($other)->status(BookingStatus::CancelledByCustomer)->create(['created_at' => now()->subDays(5)]);
        $new = Booking::factory()->forCustomer($this->customer)->forCategory($this->category)->status(BookingStatus::SearchingOffers)->create(['created_at' => now()->subDay()]);
        Booking::factory()->create(); // another customer's booking

        $all = $this->getJson('/api/v1/bookings')->assertOk()->assertJsonPath('meta.pagination.total', 3);
        $this->assertSame([$new->uuid, $mid->uuid, $old->uuid], array_column($all->json('data'), 'id'));
        $all->assertJsonStructure(['data' => [['id', 'code', 'status', 'status_label', 'booking_type', 'service' => ['id', 'code', 'name'], 'proposed_fare' => ['amount_minor', 'currency'], 'payment' => ['method', 'status'], 'created_at']]]);

        $this->assertSame([$old->uuid], array_column($this->getJson('/api/v1/bookings?status[]=completed')->json('data'), 'id'));
        $this->assertSame([$mid->uuid, $old->uuid], array_column($this->getJson('/api/v1/bookings?status[]=completed&status[]=cancelled_by_customer')->json('data'), 'id'));
        $this->assertSame([$mid->uuid], array_column($this->getJson('/api/v1/bookings?service_category_id='.$other->id)->json('data'), 'id'));
        $this->assertSame([$new->uuid], array_column($this->getJson('/api/v1/bookings?in_flight=1')->json('data'), 'id'));
        $this->assertSame([$mid->uuid, $old->uuid], array_column($this->getJson('/api/v1/bookings?in_flight=0')->json('data'), 'id'));
        $this->assertSame([$mid->uuid], array_column($this->getJson('/api/v1/bookings?from='.now()->subDays(6)->toDateString().'&to='.now()->subDays(4)->toDateString())->json('data'), 'id'));

        $page = $this->getJson('/api/v1/bookings?per_page=2')->assertOk();
        $this->assertCount(2, $page->json('data'));
        $page->assertJsonPath('meta.pagination.last_page', 2);
        $this->assertCount(1, $this->getJson('/api/v1/bookings?per_page=2&page=2')->json('data'));
    }

    public function test_list_filters_are_validated(): void
    {
        $this->actingAsCustomer($this->customer);

        $this->getJson('/api/v1/bookings?status[]=bogus')->assertStatus(422);
        $this->getJson('/api/v1/bookings?per_page=500')->assertStatus(422);
        $this->getJson('/api/v1/bookings?from=yesterday')->assertStatus(422);
        $this->getJson('/api/v1/bookings?from=2026-02-01&to=2026-01-01')->assertStatus(422);
    }
}
