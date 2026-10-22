<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Gateway-neutral meaning of a verified webhook. Each driver maps its own event names to one of these.
 */
enum WebhookEventKind: string implements HasLabel
{
    use EnumHelpers;

    case PaymentSucceeded = 'payment_succeeded';
    case PaymentFailed = 'payment_failed';
    case PaymentRequiresAction = 'payment_requires_action';
    case RefundSucceeded = 'refund_succeeded';
    case RefundFailed = 'refund_failed';
    case MethodSaved = 'method_saved';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __("enums.webhook_event_kind.{$this->value}");
    }

    public function isPayment(): bool
    {
        return in_array($this, [self::PaymentSucceeded, self::PaymentFailed, self::PaymentRequiresAction], true);
    }

    public function isRefund(): bool
    {
        return $this === self::RefundSucceeded || $this === self::RefundFailed;
    }
}
