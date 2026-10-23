<?php

namespace App\Enums\System;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The two mobile apps (the `apps` keys of GET /config/app: `customer` and `provider`).
 */
enum AppTarget: string implements HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Provider = 'provider';

    public function label(): string
    {
        return __("system.enums.app.{$this->value}");
    }
}
