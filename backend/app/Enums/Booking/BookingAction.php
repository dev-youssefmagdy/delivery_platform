<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What the customer may do with a booking right now. The server computes the list (`allowed_actions`), apps never
 * derive it from the status. Adjustments arrive with their milestone; paying and tipping online came with P3.2.
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
    case Pay = 'pay';
    case Tip = 'tip';
    case RequestAdjustment = 'request_adjustment';
    case ViewReceipt = 'view_receipt';
    case Chat = 'chat';
    case Appeal = 'appeal';
    case ViewProofs = 'view_proofs';
    case ResolveDeliveryAttempt = 'resolve_delivery_attempt';

    public function label(): string
    {
        return __("enums.booking_action.{$this->value}");
    }
}
