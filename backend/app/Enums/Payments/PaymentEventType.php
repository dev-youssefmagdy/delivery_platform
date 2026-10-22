<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Rows of a payment's timeline (`payment_events`): what happened, in order. Written once, never edited.
 */
enum PaymentEventType: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case IntentCreated = 'intent_created';
    case StatusChanged = 'status_changed';
    case WebhookApplied = 'webhook_applied';
    case WebhookIgnored = 'webhook_ignored';
    case QueryApplied = 'query_applied';
    case CashCollected = 'cash_collected';
    case Settled = 'settled';
    case TimedOut = 'timed_out';
    case AmountMismatch = 'amount_mismatch';
    case RefundRequested = 'refund_requested';
    case RefundApproved = 'refund_approved';
    case RefundRejected = 'refund_rejected';
    case RefundCompleted = 'refund_completed';
    case RefundFailed = 'refund_failed';

    public function label(): string
    {
        return __("enums.payment_event_type.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::IntentCreated, self::StatusChanged, self::WebhookApplied, self::QueryApplied => BadgeColor::Info,
            self::CashCollected, self::Settled, self::RefundCompleted, self::RefundApproved => BadgeColor::Success,
            self::WebhookIgnored, self::TimedOut, self::RefundRequested => BadgeColor::Warning,
            self::AmountMismatch, self::RefundFailed, self::RefundRejected => BadgeColor::Danger,
        };
    }
}
