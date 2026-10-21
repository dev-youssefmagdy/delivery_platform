<?php

namespace App\Enums\Pricing;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Version of the reference-price formula a fare policy uses (SRS §9.1). A formula never changes once released: a new
 * calculation is a new case, so a stored estimate can always be explained by `(formula_version, policy values)`.
 * There is deliberately no demand, time-of-day or supply multiplier in any version.
 */
enum FareFormula: int implements HasLabel
{
    use EnumHelpers;

    /**
     * recommended = max(minimum fare, base + per-km x distance + per-minute x duration + stops x stop fee + surcharges),
     * rounded to the policy step; low / high are the policy's percentages of the recommended price.
     */
    case V1 = 1;

    public function label(): string
    {
        return __("enums.fare_formula.{$this->value}");
    }
}
