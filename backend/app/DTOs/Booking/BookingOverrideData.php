<?php

namespace App\DTOs\Booking;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\DeliveryAttemptResolution;
use App\Enums\Booking\OperationsCancellationFee;

/**
 * Validated input of an operator override. `reason` is mandatory for every action; the other fields belong to one action
 * each: `fee` to the cancellation, `extendSeconds` to the search extension, `targetStatus` to a forced transition, `resolution` to the resolution of the pending failed delivery attempt.
 */
final readonly class BookingOverrideData
{
    public function __construct(
        public string $reason,
        public ?OperationsCancellationFee $fee = null,
        public ?int $extendSeconds = null,
        public ?BookingStatus $targetStatus = null,
        public ?DeliveryAttemptResolution $resolution = null,
    ) {}
}
