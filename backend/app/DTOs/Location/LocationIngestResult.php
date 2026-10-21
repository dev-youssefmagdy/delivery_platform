<?php

namespace App\DTOs\Location;

use App\Enums\Location\TrackingState;

/**
 * Outcome of one location upload. Flagged samples are counted, never hidden.
 */
final readonly class LocationIngestResult
{
    public function __construct(
        public int $received,
        public int $applied,
        public int $flagged,
        public int $stale,
        public TrackingState $trackingState,
    ) {}
}
