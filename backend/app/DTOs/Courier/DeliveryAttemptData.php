<?php

namespace App\DTOs\Courier;

use App\Enums\Booking\DeliveryAttemptOutcome;
use Illuminate\Http\UploadedFile;

/**
 * A provider's report that the hand-over failed: why, in words when the outcome asks for it, and optionally a picture.
 */
final readonly class DeliveryAttemptData
{
    public function __construct(
        public DeliveryAttemptOutcome $outcome,
        public ?string $reason = null,
        public ?UploadedFile $evidence = null,
    ) {}
}
