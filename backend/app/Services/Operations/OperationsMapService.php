<?php

namespace App\Services\Operations;

use App\DTOs\Operations\OperationsSnapshot;
use App\Enums\Booking\BookingStatus;
use App\Repositories\Contracts\Operations\OperationsMapRepositoryInterface;
use Carbon\CarbonImmutable;

/**
 * Data of the live operations map (SRS §16.6): online providers with a fresh position and active bookings, as ids,
 * coordinates, category codes and statuses only. The snapshot is the source of truth; the Echo feed only nudges it.
 */
final class OperationsMapService
{
    public function __construct(private readonly OperationsMapRepositoryInterface $map) {}

    public function snapshot(): OperationsSnapshot
    {
        $now = CarbonImmutable::now();
        $limit = (int) config('booking.operations.map_limit', 1500);
        $fresh = $now->subSeconds((int) config('booking.operations.map_position_fresh_seconds', 180));

        $providers = array_map(static fn (array $row): array => [
            'id' => $row['uuid'],
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'category' => $row['category'],
            'busy' => $row['busy'],
            'age_s' => max(0, (int) $now->diffInSeconds(CarbonImmutable::parse($row['recorded_at']), true)),
        ], $this->map->onlineProviders($fresh, $limit));

        $bookings = array_map(static fn (array $row): array => [
            'id' => $row['uuid'],
            'code' => $row['code'],
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'category' => $row['category'],
            'status' => $row['status'],
            'status_label' => BookingStatus::from($row['status'])->label(),
        ], $this->map->activeBookings($limit));

        $counts = $this->map->counts($fresh);

        return new OperationsSnapshot($providers, $bookings, $now->toIso8601String(), $counts['providers'] > $limit || $counts['bookings'] > $limit);
    }
}
