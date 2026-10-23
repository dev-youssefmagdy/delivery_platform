<?php

namespace App\Enums\Reporting;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The daily metrics tables. Each set has its own "data as of" state row.
 */
enum MetricSet: string implements HasLabel
{
    use EnumHelpers;

    case Bookings = 'bookings';
    case Finance = 'finance';
    case Operations = 'operations';

    public function label(): string
    {
        return __("reporting.metric_set.{$this->value}");
    }
}
