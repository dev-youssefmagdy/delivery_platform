<?php

namespace App\Enums\Safety;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Where an officer escalated a case. Recording "authorities contacted" is a record of what the officer did, not an integration.
 */
enum IncidentEscalationLevel: string implements HasLabel
{
    use EnumHelpers;

    case SafetyLead = 'safety_lead';
    case Operations = 'operations';
    case Legal = 'legal';
    case Authorities = 'authorities';

    public function label(): string
    {
        return __("safety.enums.escalation.{$this->value}");
    }
}
