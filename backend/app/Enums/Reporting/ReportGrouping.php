<?php

namespace App\Enums\Reporting;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How a report that reads a daily metrics table is bucketed: by market day (time series), city, zone or service category.
 */
enum ReportGrouping: string implements HasLabel
{
    use EnumHelpers;

    case Date = 'date';
    case City = 'city';
    case Zone = 'zone';
    case Category = 'category';

    public function label(): string
    {
        return __("reporting.grouping.{$this->value}");
    }
}
