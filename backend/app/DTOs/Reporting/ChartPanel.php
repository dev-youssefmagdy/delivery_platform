<?php

namespace App\DTOs\Reporting;

use App\Enums\Reporting\DataFreshness;
use App\Enums\Reporting\KpiKey;

/**
 * A dashboard chart: title, the chart payload, the drill-down link and the KPI whose definition explains it.
 */
final readonly class ChartPanel
{
    public function __construct(
        public string $id,
        public string $title,
        public ChartData $chart,
        public ?string $href = null,
        public ?KpiKey $definition = null,
        public DataFreshness $freshness = DataFreshness::Aggregated,
    ) {}
}
