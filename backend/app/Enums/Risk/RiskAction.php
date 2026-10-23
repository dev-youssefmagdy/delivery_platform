<?php

namespace App\Enums\Risk;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

enum RiskAction: string implements HasLabel
{
    use EnumHelpers;

    case Suspend = 'suspend';
    case Restrict = 'restrict';
    case Dismiss = 'dismiss';
    case Escalate = 'escalate';

    public function label(): string
    {
        return __("reputation.enums.risk_action.{$this->value}");
    }
}
