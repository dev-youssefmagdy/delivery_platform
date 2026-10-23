<?php

namespace App\Enums\Reporting;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * How current a KPI is: `live` is read from the operational tables on every load; `aggregated` comes from the daily metrics tables
 * (hourly rebuild of today and yesterday, nightly rebuild of the last days); `realtime` is a counter of the current queue state.
 */
enum DataFreshness: string implements HasLabel
{
    use EnumHelpers;

    case Live = 'live';
    case Aggregated = 'aggregated';

    public function label(): string
    {
        return __("reporting.freshness.{$this->value}");
    }
}
