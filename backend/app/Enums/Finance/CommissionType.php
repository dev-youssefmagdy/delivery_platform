<?php

namespace App\Enums\Finance;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How a commission rule version prices the platform's share (SRS §12.4).
 */
enum CommissionType: string implements HasLabel
{
    use EnumHelpers;

    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case Hybrid = 'hybrid';

    public function usesPercent(): bool
    {
        return $this !== self::Fixed;
    }

    public function usesFixed(): bool
    {
        return $this !== self::Percentage;
    }

    public function label(): string
    {
        return __("enums.commission_type.{$this->value}");
    }
}
