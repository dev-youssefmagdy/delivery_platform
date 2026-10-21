<?php

namespace App\Repositories\Contracts\Operations;

use Carbon\CarbonInterface;

/**
 * Read-only queries behind the live operations map. Rows carry ids, codes, coordinates and statuses only: no names, phone
 * numbers or addresses (SRS §16.6 privacy-controlled detail).
 */
interface OperationsMapRepositoryInterface
{
    /**
     * Online providers with a position no older than `$freshSince`.
     *
     * @return list<array{uuid: string, lat: float, lng: float, category: string|null, busy: bool, recorded_at: string}>
     */
    public function onlineProviders(CarbonInterface $freshSince, int $limit): array;

    /**
     * Bookings that are searching or assigned (their pickup point), newest first.
     *
     * @return list<array{uuid: string, code: string, lat: float, lng: float, category: string|null, status: string}>
     */
    public function activeBookings(int $limit): array;

    /**
     * Counts of the same two layers, for the headline numbers and the truncation notice.
     *
     * @return array{providers: int, bookings: int}
     */
    public function counts(CarbonInterface $freshSince): array;
}
