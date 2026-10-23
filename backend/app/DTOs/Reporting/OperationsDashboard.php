<?php

namespace App\DTOs\Reporting;

use App\Enums\Reporting\MetricSet;
use Carbon\CarbonInterface;

/**
 * Everything the dashboard shows for one admin and one filter: only the widgets the admin may open, plus the "data as of" times.
 */
final readonly class OperationsDashboard
{
    /**
     * @param list<KpiTile> $tiles
     * @param list<ChartPanel> $charts
     * @param array<string, CarbonInterface|null> $asOf metric set value => last aggregation
     * @param list<string> $currencies
     */
    public function __construct(
        public ReportFilterData $filters,
        public string $currency,
        public array $currencies,
        public array $tiles,
        public array $charts,
        public array $asOf,
    ) {}

    public function lastAggregation(): ?CarbonInterface
    {
        $times = array_filter($this->asOf);

        return $times === [] ? null : min($times);
    }

    public function isStale(int $minutes = 150): bool
    {
        $at = $this->lastAggregation();

        return $at === null || $at->diffInMinutes(now(), true) > $minutes;
    }

    public function asOfFor(MetricSet $set): ?CarbonInterface
    {
        return $this->asOf[$set->value] ?? null;
    }
}
