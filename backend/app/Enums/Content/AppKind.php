<?php

namespace App\Enums\Content;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Which mobile app a download link points to.
 */
enum AppKind: string implements HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Driver = 'driver';

    public function label(): string
    {
        return __("enums.app_kind.{$this->value}");
    }
}
