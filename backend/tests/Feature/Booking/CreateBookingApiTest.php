<?php

namespace Tests\Feature\Booking;

use App\Enums\Booking\BookingStatus;
use App\Events\Booking\BookingEnteredSearch;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuildsBookings;
use Tests\TestCase;

class CreateBookingApiTest extends TestCase
{
    use BuildsBookings;
    use RefreshDatabase;

    public function test_a_customer_creates_a_booking_that_starts_searching(): void
    {
        Event::fake([BookingEnteredSearch::class]);
        ['category' => $category, 'zone' => $zone, 'customer' => $customer] = $this->bookingWorld();
        $this->actingAsCustomer($customer);

        $response = $this->postJson('/api/v1/bookings', $this->bookingPayload($category), $this->idempotency())
            ->assertCreated()
            ->assertJsonPath('data.status', 'searching_offers')
            ->assertJsonPath('data.proposed_fare.amount_minor', 5000)
            ->assertJsonPath('data.reference_estimate.available', true);

        $booking = Booking::query()->firstOrFail();
        $this->assertSame(BookingStatus::SearchingOffers, $booking->status);
        $this->assertSame($zone->id, $booking->service_zone_id);
        $this->assertNotNull($booking->search_expires_at);
        $this->assertMatchesRegularExpression('/^BK-[A-Z2-9]{6}$/', $booking->code);
        $this->assertSame($booking->uuid, $response->json('data.id'));
        Event::assertDispatched(BookingEnteredSearch::class);
    }
}
