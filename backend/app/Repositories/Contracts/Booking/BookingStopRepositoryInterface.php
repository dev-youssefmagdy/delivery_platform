<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\Booking;
use App\Models\BookingStop;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * @extends BaseRepositoryInterface<BookingStop>
 */
interface BookingStopRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Replaces the booking's stops with `$stops` (position starts at 1).
     *
     * @param list<array{point: \App\Support\Geo\GeoPoint, address: array<string, mixed>|null, contact?: array{name: string, phone: string, note?: string}|null}> $stops
     */
    public function replaceFor(Booking $booking, array $stops): void;

    /**
     * Removes the contacts of the intermediate stops of every booking of the customer (account deletion). Returns the rows touched.
     */
    public function scrubContactsForCustomer(int $customerId): int;
}
