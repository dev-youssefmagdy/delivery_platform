<?php

namespace App\Services\Reporting;

use App\DTOs\Reporting\ChartData;
use App\DTOs\Reporting\ReportFilterData;
use App\Enums\Payout\PayoutStatus;
use App\Enums\Reporting\KpiUnit;
use App\Enums\Reporting\ReportColumnType;
use App\Enums\Reporting\ReportKey;
use App\Repositories\Contracts\Reporting\ReportQueryRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds the chart of a report from the same repository query as its table (ADR-178). Series colours follow the series' identity: the
 * slot is the position in the report's fixed series list, so filtering never repaints a series. Rows in several currencies are
 * never mixed on one axis: one currency is drawn (the filter's, otherwise the most used) and named in the summary.
 */
final class ReportChartService
{
    public function __construct(private readonly ReportQueryRepositoryInterface $reports) {}

    public function forReport(ReportKey $report, ReportFilterData $filters): ChartData
    {
        if ($report->isPlaceholder()) {
            return ChartData::empty();
        }

        $rows = $this->reports->chartRows($report, $filters)->map(static fn (mixed $row): array => $row instanceof Model ? $row->getAttributes() : (array) $row)->values()->all();
        $timeSeries = $report->isTimeSeries($filters->grouping);
        $specs = $report->chartSeries();
        $unit = $specs[0][1] ?? KpiUnit::Count;
        $currency = null;

        if ($unit === KpiUnit::Money) {
            [$rows, $currency] = $this->singleCurrency($rows, $filters->currency);
        }

        $labelKey = $this->labelKey($report, $rows);
        $labels = array_map(fn (array $row): string => $this->label($labelKey, $row), $rows);
        $series = [];

        foreach ($specs as $index => [$column, $columnUnit]) {
            $series[] = [
                'key' => $column,
                'label' => (string) __("reporting.columns.{$column}"),
                'data' => array_map(static fn (array $row): int|float|null => isset($row[$column]) ? (float) $row[$column] + 0 : null, $rows),
                'slot' => $index + 1,
            ];
        }

        $type = match (true) {
            $report === ReportKey::PayoutAgeing || $report === ReportKey::DocumentExpiry => 'bar',
            $timeSeries => 'line',
            default => 'bar',
        };

        return new ChartData(
            type: $type,
            labels: $labels,
            series: $series,
            unit: $unit,
            currency: $currency,
            labelKind: $timeSeries || $report === ReportKey::PaymentReconciliation ? 'date' : 'text',
            summary: $currency !== null ? (string) __('reporting.chart.currency_note', ['currency' => $currency]) : null,
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{0: list<array<string, mixed>>, 1: string|null}
     */
    private function singleCurrency(array $rows, ?string $preferred): array
    {
        $counts = array_count_values(array_map(static fn (array $row): string => (string) ($row['currency'] ?? ''), $rows));
        unset($counts['']);

        if ($counts === []) {
            return [$rows, $preferred];
        }

        arsort($counts);
        $currency = $preferred ?? (string) array_key_first($counts);

        return [array_values(array_filter($rows, static fn (array $row): bool => ($row['currency'] ?? $currency) === $currency)), $currency];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function labelKey(ReportKey $report, array $rows): string
    {
        foreach (['bucket_label', 'cohort', 'coupon_code', 'document_type', 'provider_ref', 'age_bucket', 'status_label', 'ran_at', 'zone_label'] as $key) {
            if (($rows[0] ?? []) !== [] && array_key_exists($key, $rows[0])) {
                return $key;
            }
        }

        foreach ($report->columns() as $column) {
            if ($column->type === ReportColumnType::Text) {
                return $column->key;
            }
        }

        return 'bucket_label';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function label(string $key, array $row): string
    {
        $value = (string) ($row[$key] ?? '');

        return match ($key) {
            'age_bucket' => __('reporting.age.'.$value).' · '.(PayoutStatus::tryFrom((string) ($row['status_label'] ?? ''))?->label() ?? ''),
            'zone_label' => $value.' · '.($row['category_label'] ?? '').' · '.sprintf('%02d:00', (int) ($row['hour_of_day'] ?? 0)),
            default => $value,
        };
    }
}
