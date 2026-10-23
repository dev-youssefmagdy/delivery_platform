<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum IncidentTrigger: string implements HasLabel
{
    use EnumHelpers;

    /** The hold-to-activate button on an active job. */
    case Sos = 'sos';
    /** The incident report form (during or after a trip). */
    case Report = 'report';

    public function label(): string
    {
        return __("safety.enums.trigger.{$this->value}");
    }
}
