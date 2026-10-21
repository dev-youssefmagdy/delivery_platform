<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What the customer may do with a booking right now. The server computes the list (`allowed_actions`), apps never
 * derive it from the status. Cancellation, adjustments and ratings arrive with their milestones.
 */
enum BookingAction: string implements HasLabel
{
    use EnumHelpers;

    case UpdateRequest = 'update_request';

    public function label(): string
    {
        return __("enums.booking_action.{$this->value}");
    }
}
