<?php

namespace App\Services\Dispatch;

use Illuminate\Database\DatabaseManager;

/**
 * Event numbers for booking streams come from `bookings.sequence`, incremented atomically in SQL, so realtime
 * events, status events and dispatch events of one booking share a single strictly increasing counter (ADR-07, ADR-89).
 * Streams that are not bookings (`provider:{uuid}` availability events) and unknown booking uuids keep counting in
 * the cache.
 */
final class BookingEventSequencer implements EventSequencer
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheEventSequencer $fallback,
    ) {}

    public function next(string $bookingUuid): int
    {
        if (str_starts_with($bookingUuid, 'provider:')) {
            return $this->fallback->next($bookingUuid);
        }

        $connection = $this->db->connection();
        $updated = $connection->update('UPDATE bookings SET sequence = LAST_INSERT_ID(sequence + 1) WHERE uuid = ?', [$bookingUuid]);

        if ($updated === 0) {
            return $this->fallback->next($bookingUuid);
        }

        return (int) ($connection->selectOne('SELECT LAST_INSERT_ID() AS sequence')->sequence ?? 0);
    }
}
