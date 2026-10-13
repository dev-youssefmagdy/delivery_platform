<?php

namespace App\Support\Money;

/**
 * Rounding applied when an operation produces a fraction of a minor unit.
 * Default for the platform is HalfUp (half away from zero) (ADR-04).
 */
enum RoundingMode: string
{
    /** 0.5 rounds away from zero: 2.5 → 3, -2.5 → -3. */
    case HalfUp = 'half_up';
    /** Banker's rounding: 2.5 → 2, 3.5 → 4. */
    case HalfEven = 'half_even';
    /** Toward zero (truncate). */
    case Down = 'down';
    /** Away from zero. */
    case Up = 'up';
}
