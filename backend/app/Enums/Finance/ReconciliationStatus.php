<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * Outcome of one `ledger:reconcile` run. A mismatch is reported and alerted, never fixed automatically.
 */
enum ReconciliationStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Ok = 'ok';
    case Mismatch = 'mismatch';

    public function label(): string
    {
        return __("enums.reconciliation_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return $this === self::Ok ? BadgeColor::Success : BadgeColor::Danger;
    }
}
