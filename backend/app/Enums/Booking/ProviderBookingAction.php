<?php

namespace App\Enums\Booking;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What the assigned provider may do with a booking right now. The server computes the list (`allowed_actions`), the
 * driver app renders its buttons from it and never infers them from the status; every endpoint re-checks.
 */
enum ProviderBookingAction: string implements HasLabel
{
    use EnumHelpers;

    case Acknowledge = 'acknowledge';
    case Arrived = 'arrived';
    case VerifyStartPin = 'verify_start_pin';
    case Start = 'start';
    case Complete = 'complete';
    case Cancel = 'cancel';
    case RateCustomer = 'rate_customer';
    case Report = 'report';
    case ConfirmCashCollected = 'confirm_cash_collected';
    case RequestAdjustment = 'request_adjustment';
    case ViewReceipt = 'view_receipt';
    case Chat = 'chat';
    case Appeal = 'appeal';
    case UploadProof = 'upload_proof';
    case MarkInTransit = 'mark_in_transit';
    case ArrivedDropoff = 'arrived_dropoff';
    case VerifyRecipientOtp = 'verify_recipient_otp';
    case ResendRecipientOtp = 'resend_recipient_otp';
    case ReportDeliveryAttempt = 'report_delivery_attempt';
    case ResolveDeliveryAttempt = 'resolve_delivery_attempt';

    public function label(): string
    {
        return __("enums.provider_booking_action.{$this->value}");
    }
}
