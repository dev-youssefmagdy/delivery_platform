<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum TicketSource: string implements HasLabel
{
    use EnumHelpers;

    case App = 'app';
    case Report = 'report';
    case Appeal = 'appeal';
    case Admin = 'admin';

    public function label(): string
    {
        return __("enums.ticket_source.{$this->value}");
    }
}
