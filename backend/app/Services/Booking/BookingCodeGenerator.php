<?php

namespace App\Services\Booking;

use App\Exceptions\ConflictException;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;

/**
 * Human-friendly public booking code ("BK-7H3K9Q"): short, upper case, no look-alike characters, unique. Random, so
 * it reveals neither volume nor order. Uniqueness is guaranteed by the database; the check only avoids retries.
 */
final class BookingCodeGenerator
{
    public function __construct(private readonly BookingRepositoryInterface $bookings) {}

    public function generate(): string
    {
        $alphabet = (string) config('booking.code.alphabet');
        $length = (int) config('booking.code.length', 6);
        $attempts = (int) config('booking.code.max_attempts', 8);

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $code = (string) config('booking.code.prefix', 'BK-');

            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            if (! $this->bookings->codeExists($code)) {
                return $code;
            }
        }

        throw ConflictException::make('booking_code_unavailable');
    }
}
