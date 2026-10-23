<?php

namespace App\DataTables\Admin;

use App\Enums\Admin\AdminPermission;
use App\Enums\Contracts\HasColor;
use App\Enums\Contracts\HasLabel;
use App\Support\Admin\Tables\TableAction;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\View\Components\Admin\Badge;
use App\View\Components\Admin\Table\Actions;
use App\View\Components\Admin\UserChip;
use BackedEnum;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Base for every admin listing (Plan §5.4).
 *
 * - Server-side processing, responsive, 25 rows, localized language, Tailwind DOM layout (the options are
 *   rendered by <x-admin.datatable> as JSON and initialised by resources/js/admin/core/datatables.js).
 * - Filters come from the linked filters card as `filters[...]` (merged into the AJAX data by the JS);
 *   subclasses build the query with `FilterData::fromRequest($this->filterRequest())`.
 * - Ordering/searching is allowlisted: the request's column definitions are rebuilt from `columns()`, so a
 *   client cannot order or search by a column the server did not mark orderable/searchable.
 * - Column helpers render translations, enum badges, money, dates (admin display timezone), booleans, user chips and
 *   permission-aware actions; their HTML columns are registered as raw automatically.
 * - Synchronous Yajra exports/print are disabled; CSV export is the queued AdminExportService flow.
 */
abstract class AdminDataTable extends DataTable
{
    /**
     * Yajra synchronous actions (print/csv/excel/pdf) are disabled on purpose.
     *
     * @var list<string>
     */
    protected array $actions = [];

    protected int $pageLength = 25;

    /**
     * Global search box. Disable on listings whose searchable data is encrypted or PII.
     */
    protected bool $globalSearch = true;

    /**
     * Default ordering as [[column index, direction]].
     *
     * @var list<array{0: int, 1: string}>
     */
    protected array $defaultOrder = [[0, 'desc']];

    /**
     * @var list<string>
     */
    private array $rawColumns = [];

    private ?Request $filterRequestOverride = null;

    /**
     * DOM id of the table; the filters card and AdminTables registry refer to it.
     */
    abstract public function tableId(): string;

    /**
     * Column definitions. Build them with column() / actionsColumnDefinition() so that ordering and
     * searching default to off and must be enabled explicitly (allowlist).
     *
     * @return list<Column>
     */
    abstract protected function columns(): array;

    /**
     * @return Builder<covariant Model>
     */
    abstract public function query(): Builder;

    /**
     * Configure the Yajra engine: edit/add columns using the helpers below.
     */
    abstract protected function configure(EloquentDataTable $table): void;

    /**
     * Permission required to queue a CSV export of this listing; null disables exporting.
     */
    public function exportPermission(): ?AdminPermission
    {
        return null;
    }

    /**
     * Rows for a CSV export of listings whose query is aggregated (no `id` to chunk by, e.g. reports). Null = the default
     * chunked read of `query()`.
     *
     * @return iterable<Model>|null
     */
    public function exportSource(): ?iterable
    {
        return null;
    }

    public function exportFilename(): string
    {
        return Str::of(class_basename(static::class))->beforeLast('DataTable')->kebab()->append('-', now()->format('Ymd-His'))->toString();
    }

    /**
     * Replays stored filters (queued exports) instead of reading the current request.
     *
     * @param array<string, mixed> $filters raw `filters[...]` input shape
     */
    public function usingFilters(array $filters): static
    {
        $this->filterRequestOverride = Request::create('/', 'GET', ['filters' => $filters]);

        return $this;
    }

    /**
     * The request the filter DTO is read from.
     */
    protected function filterRequest(): Request
    {
        return $this->filterRequestOverride ?? request();
    }

    /**
     * @param Builder<covariant Model> $query
     */
    public function dataTable(Builder $query): EloquentDataTable
    {
        $table = new EloquentDataTable($query);
        $this->rawColumns = [];

        $this->configure($table);

        return $table->rawColumns(array_values(array_unique($this->rawColumns)));
    }

    public function ajax(): JsonResponse
    {
        $this->enforceAllowlist();

        $response = parent::ajax();
        $payload = $response->getData(true);

        // Yajra appends SQL + raw input when APP_DEBUG is on; never expose them (SRS §20.7).
        if (is_array($payload) && (isset($payload['queries']) || isset($payload['input']))) {
            unset($payload['queries'], $payload['input']);
            $response->setData($payload);
        }

        return $response;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId($this->tableId())
            ->setTableAttribute('class', 'admin-table display nowrap')
            ->setTableAttribute('style', 'width:100%')
            ->columns($this->columns())
            ->ajax(['url' => $this->ajaxUrl(), 'type' => 'GET'])
            ->parameters($this->parameters());
    }

    /**
     * Options handed to DataTables (language is added by <x-admin.datatable>).
     *
     * @return array<string, mixed>
     */
    protected function parameters(): array
    {
        return [
            'serverSide' => true,
            'processing' => true,
            'responsive' => true,
            'autoWidth' => false,
            'searching' => $this->globalSearch,
            'searchDelay' => 400,
            'pageLength' => $this->pageLength,
            'lengthMenu' => [10, 25, 50, 100],
            'order' => $this->defaultOrder,
            'layout' => [
                'topStart' => 'pageLength',
                'topEnd' => $this->globalSearch ? 'search' : null,
                'bottomStart' => 'info',
                'bottomEnd' => 'paging',
            ],
        ];
    }

    protected function ajaxUrl(): string
    {
        return request()->url();
    }

    /**
     * Column definition with ordering/searching OFF unless explicitly allowed.
     */
    protected function column(string $data, string $title, bool $orderable = false, bool $searchable = false, ?string $name = null, bool $exportable = true): Column
    {
        return Column::make($data, $name ?? $data)
            ->title($title)
            ->orderable($orderable)
            ->searchable($searchable)
            ->exportable($exportable)
            ->printable($exportable);
    }

    protected function actionsColumnDefinition(): Column
    {
        return Column::computed('actions', __('admin.common.actions'))
            ->orderable(false)
            ->searchable(false)
            ->exportable(false)
            ->printable(false)
            ->addClass('dt-actions text-end')
            ->responsivePriority(1);
    }

    /**
     * Marks a column whose cells are trusted HTML (already escaped by the component that produced it).
     */
    protected function rawColumn(string $column): void
    {
        $this->rawColumns[] = $column;
    }

    /**
     * Renders an enum implementing HasLabel&HasColor as <x-admin.badge>.
     */
    protected function enumBadgeColumn(EloquentDataTable $table, string $column): void
    {
        $table->editColumn($column, function (Model $row) use ($column): string {
            $value = $row->getAttribute($column);

            return $value instanceof HasLabel && $value instanceof HasColor
                ? Blade::renderComponent(new Badge($value))
                : e($this->stringify($value));
        });

        $this->rawColumns[] = $column;
    }

    /**
     * Shows a spatie/laravel-translatable attribute in the current locale (falls back to the default locale).
     */
    protected function translatedColumn(EloquentDataTable $table, string $column): void
    {
        $table->editColumn($column, static fn (Model $row): string => method_exists($row, 'getTranslation')
            ? (string) $row->getTranslation($column, app()->getLocale(), true)
            : (string) $row->getAttribute($column));
    }

    /**
     * Formats a Money attribute (MoneyCast) in the current locale. Plain-text columns are escaped by Yajra.
     */
    protected function moneyColumn(EloquentDataTable $table, string $column, ?string $attribute = null): void
    {
        $table->editColumn($column, function (Model $row) use ($column, $attribute): string {
            $money = $row->getAttribute($attribute ?? $column);

            return $money instanceof Money ? app(MoneyFormatter::class)->format($money) : '—';
        });
    }

    /**
     * Formats a date/datetime attribute in the admin display timezone (stored UTC).
     */
    protected function dateColumn(EloquentDataTable $table, string $column, bool $withTime = true): void
    {
        $table->editColumn($column, function (Model $row) use ($column, $withTime): string {
            $value = $row->getAttribute($column);

            return $value instanceof CarbonInterface ? $this->formatDate($value, $withTime) : '—';
        });
    }

    protected function boolColumn(EloquentDataTable $table, string $column): void
    {
        $table->editColumn($column, static fn (Model $row): string => Blade::renderComponent(new Badge(
            label: $row->getAttribute($column) ? __('admin.common.yes') : __('admin.common.no'),
            color: $row->getAttribute($column) ? 'success' : 'neutral',
        )));

        $this->rawColumns[] = $column;
    }

    /**
     * Renders <x-admin.user-chip>. The resolver returns [name, subtitle|null] or null.
     *
     * @template TModel of Model
     *
     * @param Closure(TModel): (array{0: string, 1?: string|null}|null) $resolve
     */
    protected function userColumn(EloquentDataTable $table, string $column, Closure $resolve): void
    {
        $table->editColumn($column, static function (Model $row) use ($resolve): string {
            $user = $resolve($row);

            return $user === null ? '—' : Blade::renderComponent(new UserChip($user[0], $user[1] ?? null));
        });

        $this->rawColumns[] = $column;
    }

    /**
     * Permission-aware row actions (each TableAction is checked against its policy ability).
     *
     * @template TModel of Model
     *
     * @param Closure(TModel): list<TableAction> $actions
     */
    protected function actionsColumn(EloquentDataTable $table, Closure $actions): void
    {
        $table->addColumn('actions', static fn (Model $row): string => Blade::renderComponent(new Actions($actions($row))));

        $this->rawColumns[] = 'actions';
    }

    /**
     * Headings for CSV exports: every exportable column.
     *
     * @return array<string, string> data key => heading
     */
    public function exportHeadings(): array
    {
        $headings = [];

        foreach ($this->columns() as $column) {
            if ($column->exportable !== false && $column->data !== 'actions') {
                $headings[(string) $column->data] = strip_tags((string) $column->title);
            }
        }

        return $headings;
    }

    /**
     * One CSV row. Override for custom formatting; defaults to the column data keys.
     *
     * @return list<string>
     */
    public function exportRow(Model $row): array
    {
        $values = [];

        foreach (array_keys($this->exportHeadings()) as $key) {
            $values[] = $this->stringify(data_get($row, $key), latinDigits: true);
        }

        return $values;
    }

    protected function formatDate(CarbonInterface $value, bool $withTime = true): string
    {
        return $value->copy()
            ->setTimezone((string) config('admin.display_timezone', 'UTC'))
            ->locale(app()->getLocale())
            ->translatedFormat($withTime ? 'Y-m-d H:i' : 'Y-m-d');
    }

    protected function stringify(mixed $value, bool $latinDigits = false): string
    {
        return match (true) {
            $value === null => '',
            $value instanceof HasLabel => $value->label(),
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof Money => app(MoneyFormatter::class)->format($value, latinDigits: $latinDigits),
            $value instanceof CarbonInterface => $value->copy()->setTimezone((string) config('admin.display_timezone', 'UTC'))->format('Y-m-d H:i'),
            is_bool($value) => $value ? __('admin.common.yes') : __('admin.common.no'),
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            is_scalar($value) => (string) $value,
            default => '',
        };
    }

    /**
     * Rebuilds the request's `columns`/`order`/`search` from the server definitions (allowlist).
     */
    private function enforceAllowlist(): void
    {
        $request = request();
        $columns = [];

        foreach ($this->columns() as $index => $column) {
            $columns[$index] = [
                'data' => (string) $column->data,
                'name' => (string) ($column->name ?? $column->data),
                'searchable' => $this->globalSearch && $column->searchable ? 'true' : 'false',
                'orderable' => $column->orderable ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        $order = [];

        foreach ((array) $request->input('order', []) as $item) {
            $index = is_array($item) && is_numeric($item['column'] ?? null) ? (int) $item['column'] : -1;
            $direction = is_array($item) ? strtolower((string) ($item['dir'] ?? '')) : '';

            if (($columns[$index]['orderable'] ?? 'false') === 'true' && in_array($direction, ['asc', 'desc'], true)) {
                $order[] = ['column' => $index, 'dir' => $direction];
            }
        }

        $search = $request->input('search.value');

        $request->merge([
            'columns' => $columns,
            'order' => $order,
            'search' => [
                'value' => $this->globalSearch && is_string($search) ? mb_substr($search, 0, 100) : '',
                'regex' => 'false',
            ],
        ]);
    }
}
