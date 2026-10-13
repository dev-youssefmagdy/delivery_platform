<?php

namespace App\Enums\Common;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum ActorType: string implements HasLabel
{
    use EnumHelpers;

    case Customer = 'customer';
    case Provider = 'provider';
    case Admin = 'admin';
    case System = 'system';

    public function label(): string
    {
        return __("enums.actor_type.{$this->value}");
    }
}
