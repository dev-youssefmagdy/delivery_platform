<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Side of a journal line. Every entry has Σdebit = Σcredit per currency (Plan I5).
 */
enum EntrySide: string implements HasLabel
{
    use EnumHelpers;

    case Debit = 'debit';
    case Credit = 'credit';

    public function opposite(): self
    {
        return $this === self::Debit ? self::Credit : self::Debit;
    }

    public function label(): string
    {
        return __("enums.entry_side.{$this->value}");
    }
}
