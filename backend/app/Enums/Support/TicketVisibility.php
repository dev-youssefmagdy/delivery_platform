<?php

namespace App\Enums\Support;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Who may read a ticket message. Internal notes are never returned by the user-facing API.
 */
enum TicketVisibility: string implements HasLabel
{
    use EnumHelpers;

    case Public = 'public';
    case Internal = 'internal';

    public function label(): string
    {
        return __("enums.ticket_visibility.{$this->value}");
    }
}
