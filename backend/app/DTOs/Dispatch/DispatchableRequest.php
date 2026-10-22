<?php

namespace App\DTOs\Dispatch;

use App\Support\Geo\GeoPoint;
use Carbon\CarbonImmutable;

/**
 * The minimal contract between dispatch and a booking. Dispatch never reads the bookings table: the
 * `DispatchableRequestSource` (DispatchableBookingSource, P2.3) builds this from a real booking. `summary` must already be safe for every
 * dispatched provider (staged disclosure, Plan I10): service name, fare, rounded pickup area, never a phone or address.
 */
final readonly class DispatchableRequest
{
    /**
     * @param list<int> $vehicleCategoryIds acceptable vehicle categories (empty = every category linked to the service)
     * @param int $generation `bookings.request_version`: wave jobs of an older version of the request stop
     * @param array<string, mixed> $summary
     */
    public function __construct(
        public int $bookingId,
        public string $bookingUuid,
        public int $serviceCategoryId,
        public int $cityId,
        public GeoPoint $pickup,
        public CarbonImmutable $searchExpiresAt,
        public bool $searching = true,
        public array $vehicleCategoryIds = [],
        public ?int $passengerCount = null,
        public array $summary = [],
        public int $generation = 1,
        /** Load of a moving / freight request: a vehicle that cannot carry it is not dispatched (SRS §10.1). */
        public ?float $requiredPayloadKg = null,
        public ?float $requiredVolumeM3 = null,
    ) {}

    public function isOpenAt(CarbonImmutable $now): bool
    {
        return $this->searching && $this->searchExpiresAt->greaterThan($now);
    }
}
