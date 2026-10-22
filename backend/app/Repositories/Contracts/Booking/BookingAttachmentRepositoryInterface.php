<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingAttachment;
use Illuminate\Support\Collection;

interface BookingAttachmentRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function insert(array $attributes): BookingAttachment;

    public function findOfCustomer(int $customerId, string $uuid): ?BookingAttachment;

    public function findInBooking(int $bookingId, string $uuid): ?BookingAttachment;

    /**
     * @param list<string> $uuids
     * @return Collection<int, BookingAttachment> rows (locked for update) owned by the customer
     */
    public function lockOwned(int $customerId, array $uuids): Collection;

    /**
     * @return Collection<int, BookingAttachment>
     */
    public function forBooking(int $bookingId): Collection;

    public function countUnattached(int $customerId): int;

    public function countForBooking(int $bookingId, string $kind): int;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(BookingAttachment $attachment, array $attributes): void;

    public function delete(BookingAttachment $attachment): void;

    /**
     * Deletes the rows of the customer's bookings (and their unattached uploads) and returns the stored paths to remove.
     *
     * @return list<string>
     */
    public function eraseForCustomer(int $customerId): array;
}
