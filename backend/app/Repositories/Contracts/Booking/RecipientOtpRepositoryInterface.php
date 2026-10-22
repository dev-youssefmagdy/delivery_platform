<?php

namespace App\Repositories\Contracts\Booking;

use App\Models\BookingRecipientOtp;

interface RecipientOtpRepositoryInterface
{
    public function find(int $bookingId): ?BookingRecipientOtp;

    /**
     * Call inside a transaction that already holds the booking lock.
     */
    public function lockForBooking(int $bookingId): ?BookingRecipientOtp;

    /**
     * @param array<string, mixed> $attributes
     */
    public function upsert(int $bookingId, array $attributes): BookingRecipientOtp;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(BookingRecipientOtp $otp, array $attributes): BookingRecipientOtp;

    /**
     * Removes the code hashes of the customer's bookings (account deletion).
     */
    public function purgeForCustomer(int $customerId): int;
}
