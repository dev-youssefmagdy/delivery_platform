<?php

namespace App\Repositories\Eloquent\Booking;

use App\Models\BookingShareLink;
use App\Repositories\Contracts\Booking\ShareLinkRepositoryInterface;
use Carbon\CarbonInterface;

final class ShareLinkRepository implements ShareLinkRepositoryInterface
{
    public function create(int $bookingId, string $tokenHash, int $createdByUserId, CarbonInterface $expiresAt): BookingShareLink
    {
        return BookingShareLink::query()->create([
            'booking_id' => $bookingId,
            'token_hash' => $tokenHash,
            'created_by_user_id' => $createdByUserId,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findByHash(string $tokenHash): ?BookingShareLink
    {
        return BookingShareLink::query()->with('booking')->where('token_hash', $tokenHash)->first();
    }

    public function revokeForBooking(int $bookingId, CarbonInterface $now): int
    {
        return BookingShareLink::query()->where('booking_id', $bookingId)->whereNull('revoked_at')->where('expires_at', '>', $now)->update(['revoked_at' => $now]);
    }
}
