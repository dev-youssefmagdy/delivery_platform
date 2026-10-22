<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * A cash reconciliation of one provider and period: opened with the computed cash and commission figures, then settled
 * (money received, posted to the ledger) or cancelled.
 */
enum CashReconciliationStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case Settled = 'settled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.cash_reconciliation_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Open => BadgeColor::Warning,
            self::Settled => BadgeColor::Success,
            self::Cancelled => BadgeColor::Neutral,
        };
    }
}
