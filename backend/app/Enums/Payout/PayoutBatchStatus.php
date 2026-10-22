<?php

namespace App\Enums\Payout;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Enums\Support\BadgeColor;

/**
 * A batch groups approved payouts for one transfer run: open (just created), exported (the transfer file was downloaded)
 * and completed (every payout in it is paid, failed or reversed).
 */
enum PayoutBatchStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Open = 'open';
    case Exported = 'exported';
    case Completed = 'completed';

    public function label(): string
    {
        return __("enums.payout_batch_status.{$this->value}");
    }

    public function color(): BadgeColor
    {
        return match ($this) {
            self::Open => BadgeColor::Warning,
            self::Exported => BadgeColor::Info,
            self::Completed => BadgeColor::Success,
        };
    }
}
