<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingShareLink;
use Carbon\CarbonInterface;

interface ShareLinkRepositoryInterface
{
    public function create(int $bookingId, string $tokenHash, int $createdByUserId, CarbonInterface $expiresAt): BookingShareLink;

    public function findByHash(string $tokenHash): ?BookingShareLink;

    /**
     * Ends every still-active link of the booking (trip finished or cancelled, or the customer revoked them).
     *
     * @return int links revoked
     */
    public function revokeForBooking(int $bookingId, CarbonInterface $now): int;
}
