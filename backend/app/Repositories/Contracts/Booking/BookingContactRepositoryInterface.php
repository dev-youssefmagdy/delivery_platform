<?php

namespace App\Repositories\Contracts\Booking;

use App\Enums\Booking\ContactRole;
use App\Models\Booking;
use App\Models\BookingContact;
use Illuminate\Support\Collection;

interface BookingContactRepositoryInterface
{
    /**
     * @return Collection<int, BookingContact>
     */
    public function forBooking(int $bookingId): Collection;

    public function find(int $bookingId, ContactRole $role): ?BookingContact;

    /**
     * Replaces the booking's contacts with `$contacts` (role value => name and phone in clear text; encrypted here).
     *
     * @param array<string, array{name: string, phone: string}> $contacts
     */
    public function replace(Booking $booking, array $contacts): void;

    /**
     * Removes the names and phones of every booking of the customer (account deletion). Returns the rows touched.
     */
    public function scrubForCustomer(int $customerId): int;
}
