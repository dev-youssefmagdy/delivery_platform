<?php

namespace App\Enums\Payments;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * A refund's life: requested (waiting for a second admin when the amount reaches the dual-approval threshold),
 * approved, sent to the gateway (processing), then completed (ledger reversal posted) or failed. Rejected and
 * cancelled never reach the gateway.
 */
enum RefundStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("enums.refund_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::PendingApproval => BadgeColor::Warning,
            self::Approved, self::Processing => BadgeColor::Info,
            self::Completed => BadgeColor::Success,
            self::Failed, self::Rejected => BadgeColor::Danger,
        };
    }

    /**
     * Money may still leave (or be reserved) for this refund: counts against what is refundable.
     */
    public function reservesAmount(): bool
    {
        return in_array($this, [self::PendingApproval, self::Approved, self::Processing, self::Completed], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::PendingApproval, self::Approved, self::Processing], true);
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::PendingApproval, self::Approved, self::Processing];
    }

    /**
     * @return list<self>
     */
    public static function reserving(): array
    {
        return [self::PendingApproval, self::Approved, self::Processing, self::Completed];
    }
}
