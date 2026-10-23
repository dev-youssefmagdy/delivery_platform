<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum TrustedContactActionType: string implements HasLabel
{
    use EnumHelpers;

    case SosAlert = 'sos_alert';
    case TripShare = 'trip_share';

    public function label(): string
    {
        return __("safety.enums.contact_action.{$this->value}");
    }
}
