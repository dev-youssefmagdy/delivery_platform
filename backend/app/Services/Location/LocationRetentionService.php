<?php

namespace App\Services\Location;

use App\Repositories\Contracts\Location\ProviderLocationRepositoryInterface;

/**
 * Deletes old location history in chunks (ADR-83): rows without a booking after `retention_days`, rows of a booking after
 * `booking_retention_days`.
 */
final class LocationRetentionService
{
    private const CHUNK = 5000;

    public function __construct(private readonly ProviderLocationRepositoryInterface $history) {}

    /**
     * @return int rows deleted
     */
    public function prune(): int
    {
        $deleted = 0;

        foreach ([[(int) config('location.retention_days'), true], [(int) config('location.booking_retention_days'), false]] as [$days, $withoutBooking]) {
            do {
                $count = $this->history->pruneBefore(now()->subDays($days), $withoutBooking, self::CHUNK);
                $deleted += $count;
            } while ($count === self::CHUNK);
        }

        return $deleted;
    }
}
