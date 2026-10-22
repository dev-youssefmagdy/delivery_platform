<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * What the platform did with a received webhook (`gateway_webhook_events.outcome`).
 */
enum WebhookOutcome: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Received = 'received';
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case Ignored = 'ignored';
    case Unmatched = 'unmatched';
    case InvalidSignature = 'invalid_signature';
    case ReplayWindow = 'replay_window';
    case AmountMismatch = 'amount_mismatch';
    case Failed = 'failed';

    public function label(): string
    {
        return __("enums.webhook_outcome.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Processed => BadgeColor::Success,
            self::Received, self::Duplicate => BadgeColor::Neutral,
            self::Ignored => BadgeColor::Info,
            self::Unmatched => BadgeColor::Warning,
            self::InvalidSignature, self::ReplayWindow, self::AmountMismatch, self::Failed => BadgeColor::Danger,
        };
    }

    /**
     * An operator may run the event through the processor again (with a reason).
     */
    public function canReprocess(): bool
    {
        return in_array($this, [self::Failed, self::Unmatched, self::AmountMismatch], true);
    }
}
