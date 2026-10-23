<?php

namespace App\DataTables\Admin\Reporting;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Reporting\ReportColumn;
use App\DTOs\Reporting\ReportFilterData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Finance\ReconciliationStatus;
use App\Enums\Payout\PayoutStatus;
use App\Enums\Reporting\ReportColumnType;
use App\Enums\Reporting\ReportGrouping;
use App\Enums\Reporting\ReportKey;
use App\Repositories\Contracts\Reporting\ReportQueryRepositoryInterface;
use App\Services\Reporting\ReportChartService;
use App\Support\Reporting\ReportValueFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * One Yajra table for every report (SRS 16.14): columns, formatting and the chart payload come from `ReportKey`; the query is
 * `ReportQueryRepository::datatableQuery()`, shared with the queued CSV export. The JSON carries `chart` next to the rows so the
 * chart above the table refreshes with every filter. Personal-data columns exist only for `reports.export_pii` (a queued export replays
 * the flag stored by the export request, because the job has no signed-in admin).
 */
class ReportsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'report-table';

    protected bool $globalSearch = false;

    private bool $replay = false;

    private ?ReportKey $replayKey = null;

    public function __construct(
        protected readonly ReportQueryRepositoryInterface $reports,
        protected readonly ReportChartService $charts,
        protected readonly ReportValueFormatter $format,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    public function usingFilters(array $filters): static
    {
        $this->replay = true;
        $this->replayKey = ReportKey::tryFromValue(isset($filters['report']) ? (string) $filters['report'] : null);

        return parent::usingFilters($filters);
    }

    public function report(): ReportKey
    {
        $bound = request()->route('report');

        return $this->replayKey ?? ($bound instanceof ReportKey ? $bound : throw new \LogicException('No report selected.'));
    }

    public function filters(): ReportFilterData
    {
        return ReportFilterData::fromRequest($this->filterRequest());
    }

    public function exportPermission(): AdminPermission
    {
        return AdminPermission::ReportsExport;
    }

    public function exportFilename(): string
    {
        return 'report-'.$this->report()->value.'-'.now()->format('Ymd-His');
    }

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return $this->reports->datatableQuery($this->report(), $this->filters(), $this->includesPii());
    }

    public function exportSource(): iterable
    {
        return $this->query()->limit((int) config('admin.exports.report_max_rows', 100000))->get();
    }

    /**
     * Whether personal-data columns are part of this table (live: the elevated permission; replay: what the export request stored).
     */
    public function includesPii(): bool
    {
        return $this->replay ? $this->filters()->pii : Gate::allows('viewPii', $this->report());
    }

    /**
     * @return list<ReportColumn>
     */
    public function visibleColumns(): array
    {
        $pii = $this->includesPii();

        return array_values(array_filter($this->report()->columns(), static fn (ReportColumn $column): bool => $pii || ! $column->pii));
    }

    protected function parameters(): array
    {
        $columns = $this->visibleColumns();
        $first = $columns[0] ?? null;
        $order = [[0, 'desc']];

        if ($first !== null && ! ($first->key === 'bucket_label' && $this->filters()->grouping === ReportGrouping::Date)) {
            foreach ($columns as $index => $column) {
                if ($column->type !== ReportColumnType::Text) {
                    $order = [[$index, 'desc']];
                    break;
                }
            }
        }

        $this->defaultOrder = $order;

        return parent::parameters();
    }

    protected function columns(): array
    {
        return array_map(function (ReportColumn $column): \Yajra\DataTables\Html\Column {
            $sort = $column->key === 'bucket_label' ? 'bucket' : $column->key;

            return $this->column($column->key, $column->label(), orderable: $column->orderable, name: $sort)
                ->addClass($column->type === ReportColumnType::Text ? '' : 'text-end');
        }, $this->visibleColumns());
    }

    protected function configure(EloquentDataTable $table): void
    {
        $columns = $this->visibleColumns();
        $table->only(array_map(static fn (ReportColumn $column): string => $column->key, $columns));

        foreach ($columns as $column) {
            $table->editColumn($column->key, fn (Model $row): string => e($this->cell($column, $row, false)));
        }

        if ($this->shouldIncludeChart()) {
            $table->with('chart', fn (): array => $this->charts->forReport($this->report(), $this->filters())->toArray());
        }
    }

    /**
     * The chart is sent with the first page only (filters reset the paging), not with every sort or page change.
     */
    private function shouldIncludeChart(): bool
    {
        return (int) request()->input('start', 0) === 0 && ! $this->replay;
    }

    public function exportHeadings(): array
    {
        $headings = [];

        foreach ($this->visibleColumns() as $column) {
            $headings[$column->key] = $column->label();
        }

        return $headings;
    }

    public function exportRow(Model $row): array
    {
        return array_map(fn (ReportColumn $column): string => $this->cell($column, $row, true), $this->visibleColumns());
    }

    private function cell(ReportColumn $column, Model $row, bool $latinDigits): string
    {
        $value = $row->getAttribute($column->key);

        if ($column->type === ReportColumnType::Text) {
            return $this->text($column->key, $value, $row);
        }

        return $this->format->format($column->type, $value, (string) ($row->getAttribute('currency') ?? ''), $latinDigits);
    }

    private function text(string $key, mixed $value, Model $row): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'status_label' => $this->report() === ReportKey::PayoutAgeing
                ? (PayoutStatus::tryFrom((string) $value)?->label() ?? (string) $value)
                : (ReconciliationStatus::tryFrom((string) $value)?->label() ?? (string) $value),
            'age_bucket' => (string) __('reporting.age.'.Str::of((string) $value)->toString()),
            default => (string) $value,
        };
    }
}
