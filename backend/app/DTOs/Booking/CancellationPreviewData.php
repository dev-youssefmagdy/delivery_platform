<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\CancellationStage;
use App\Enums\Common\ActorType;

/**
 * Scenario of the cancellation policy calculator: who cancels, at which stage, how long after selection / arrival, the fare
 * and the evidence flags. Minutes, minor units; never persisted.
 */
final readonly class CancellationPreviewData
{
    public function __construct(
        public ActorType $actor,
        public CancellationStage $stage,
        public ?CancellationReason $reason,
        public int $fareMinor,
        public int $minutesSinceSelection,
        public int $minutesSinceArrival,
        public bool $arrivalVerified,
        public bool $reportVerified,
        public string $currency,
    ) {}
}
