<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What the customer may do with a booking right now. The server computes the list (`allowed_actions`), apps never
 * derive it from the status. Adjustments and payment actions arrive with their milestones.
 */
enum BookingAction: string implements HasLabel
{
    use EnumHelpers;

    case UpdateRequest = 'update_request';
    case SelectOffer = 'select_offer';
    case Cancel = 'cancel';
    case Share = 'share';
    case Rate = 'rate';
    case Report = 'report';

    public function label(): string
    {
        return __("enums.booking_action.{$this->value}");
    }
}
