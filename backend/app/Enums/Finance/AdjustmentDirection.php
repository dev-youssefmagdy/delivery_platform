<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * What an adjustment does to the wallet it targets: credit raises the wallet balance (money the platform owes the holder),
 * debit lowers it. The counterpart is always the platform's `adjustments` account.
 */
enum AdjustmentDirection: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Credit = 'credit';
    case Debit = 'debit';

    public function label(): string
    {
        return __("enums.adjustment_direction.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return $this === self::Credit ? BadgeColor::Success : BadgeColor::Danger;
    }
}
