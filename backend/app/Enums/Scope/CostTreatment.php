<?php

namespace App\Enums\Scope;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How tolls or parking are treated by an offer: inside the fare, charged at cost against a receipt (an adjustment the
 * customer approves) or left to the customer outside the app.
 */
enum CostTreatment: string implements HasLabel
{
    use EnumHelpers;

    case Included = 'included';
    case AtCostWithEvidence = 'at_cost_with_evidence';
    case Excluded = 'excluded';

    public function label(): string
    {
        return __("enums.cost_treatment.{$this->value}");
    }
}
