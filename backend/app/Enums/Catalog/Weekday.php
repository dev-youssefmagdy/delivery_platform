<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * ISO-8601 weekday numbers used by a category's service hours.
 */
enum Weekday: int implements HasLabel
{
    use EnumHelpers;

    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function label(): string
    {
        return __("enums.weekday.{$this->value}");
    }
}
