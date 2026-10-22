<?php

namespace App\Services\Booking;

use App\DTOs\Dispatch\DispatchableRequest;
use App\Models\Booking;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Services\Dispatch\DispatchableRequestSource;
use Carbon\CarbonImmutable;

/**
 * Builds the dispatch contract from a booking (ADR-86, ADR-89). `searching` is true only while the booking is in a
 * searching status, so a cancelled, expired or already assigned booking stops the wave chain. The summary is what
 * every dispatched provider may see before selection (staged disclosure, Plan I10): service, fare, rounded areas,
 * distance and passengers. No customer identity, phone, exact address or free-text details.
 */
final class DispatchableBookingSource implements DispatchableRequestSource
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    public function find(int $bookingId): ?DispatchableRequest
    {
        $booking = $this->bookings->find($bookingId);

        return $booking instanceof Booking ? $this->fromBooking($booking) : null;
    }

    public function fromBooking(Booking $booking): DispatchableRequest
    {
        /** @var list<array{id: int}> $vehicles */
        $vehicles = (array) ($booking->service_snapshot['vehicle_categories'] ?? []);
        $route = (array) ($booking->route_snapshot ?? []);

        return new DispatchableRequest(
            bookingId: $booking->id,
            bookingUuid: $booking->uuid,
            serviceCategoryId: $booking->service_category_id,
            cityId: $booking->city_id,
            pickup: $booking->pickup_point,
            searchExpiresAt: $booking->search_expires_at !== null ? CarbonImmutable::instance($booking->search_expires_at) : CarbonImmutable::now(),
            searching: $booking->status->isSearching(),
            vehicleCategoryIds: array_map(static fn (array $vehicle): int => (int) $vehicle['id'], $vehicles),
            passengerCount: $booking->passengers,
            summary: [
                'booking_type' => $booking->booking_type->value,
                'service' => $booking->service_snapshot['category']['name'] ?? null,
                'fare' => ['amount_minor' => $booking->proposed_fare_minor, 'currency' => $booking->currency],
                'dropoff_area' => $booking->dropoff_point === null ? null : ['lat' => round($booking->dropoff_point->lat, 2), 'lng' => round($booking->dropoff_point->lng, 2)],
                'distance_m' => $route['distance_m'] ?? null,
                'duration_s' => $route['duration_s'] ?? null,
                'stops' => (int) ($booking->policy_snapshot['stops_count'] ?? 0),
                'passengers' => $booking->passengers,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                'restricted_pickup' => (bool) ($booking->policy_snapshot['zone']['restricted_pickup'] ?? false),
                'payment_method' => $booking->payment_method->value,
                'request_version' => $booking->request_version,
                // Moving / freight (P4.2): the load and the requested scope are not personal data, so every dispatched provider may see them.
                'load' => $booking->cargo_weight_kg === null && $booking->cargo_volume_m3 === null ? null : [
                    'weight_kg' => $booking->cargo_weight_kg === null ? null : (float) $booking->cargo_weight_kg,
                    'volume_m3' => $booking->cargo_volume_m3 === null ? null : (float) $booking->cargo_volume_m3,
                ],
                'pickup_window' => $booking->pickup_window_start === null ? null : ['start' => $booking->pickup_window_start->toIso8601ZuluString(), 'end' => $booking->pickup_window_end?->toIso8601ZuluString()],
                'requested_scope' => $booking->requested_scope,
            ],
            generation: $booking->request_version,
            requiredPayloadKg: $booking->cargo_weight_kg === null ? null : (float) $booking->cargo_weight_kg,
            requiredVolumeM3: $booking->cargo_volume_m3 === null ? null : (float) $booking->cargo_volume_m3,
        );
    }
}
