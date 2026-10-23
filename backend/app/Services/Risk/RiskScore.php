<?php

namespace App\Services\Risk;

/**
 * Maps a measured value onto the 0-100 score of a signal: exactly the threshold scores 40 (worth a look), the cap and above score 100,
 * linear in between. The mapping is the same for every detector so scores are comparable in the queue.
 */
final class RiskScore
{
    public const AT_THRESHOLD = 40;

    public static function scale(float $value, float $threshold, float $cap): int
    {
        if ($value < $threshold) {
            return 0;
        }

        if ($cap <= $threshold || $value >= $cap) {
            return 100;
        }

        return (int) round(self::AT_THRESHOLD + (100 - self::AT_THRESHOLD) * (($value - $threshold) / ($cap - $threshold)));
    }
}
