<?php

namespace App\Support\Booking;

use Closure;

/**
 * The one door through which `bookings.status` may change (ADR-16). `Booking` refuses to save a changed status
 * unless the write happens inside `run()`, and only `BookingTransitionService` calls it (a test greps the source
 * tree to keep it that way). Inserting a booking in its initial status does not need it.
 */
final class BookingStatusGate
{
    private static int $depth = 0;

    /**
     * @template T
     *
     * @param Closure(): T $write
     * @return T
     */
    public static function run(Closure $write): mixed
    {
        self::$depth++;

        try {
            return $write();
        } finally {
            self::$depth--;
        }
    }

    public static function isOpen(): bool
    {
        return self::$depth > 0;
    }
}
