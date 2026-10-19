<?php

namespace App\DataTables\Admin\Markets;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Markets\CurrencyFilterData;
use App\Models\Currency;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Currencies listing (filters: search, active, created range).
 */
final class CurrenciesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'currencies-table';

    protected array $defaultOrder = [[1, 'asc']];

    public function __construct(private readonly CurrencyRepositoryInterface $currencies)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Currency>
     */
    public function query(): Builder
    {
        return $this->currencies->datatableQuery(CurrencyFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('code', __('admin.currencies.fields.code'), orderable: true),
            $this->column('symbol_ar', __('admin.currencies.fields.symbol_ar')),
            $this->column('symbol_en', __('admin.currencies.fields.symbol_en')),
            $this->column('exponent', __('admin.currencies.fields.exponent'), orderable: true),
            $this->column('active', __('admin.currencies.fields.active'), orderable: true),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->boolColumn($table, 'active');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, fn (Currency $currency): array => [
            TableAction::view(route('admin.currencies.show', $currency), $currency),
            TableAction::edit(route('admin.currencies.edit', $currency), $currency),
            TableAction::delete(route('admin.currencies.destroy', $currency), $currency, self::TABLE_ID),
        ]);
    }
}
