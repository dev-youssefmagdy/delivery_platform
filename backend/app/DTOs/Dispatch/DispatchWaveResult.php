<?php

namespace App\DTOs\Dispatch;

/**
 * What one wave did. `notified` counts the providers this wave sent the request to; it is never a claim about how many
 * drivers exist or were reached overall.
 */
final readonly class DispatchWaveResult
{
    public function __construct(
        public int $wave,
        public float $radiusKm,
        public int $notified,
        public bool $nextWaveScheduled,
    ) {}
}
