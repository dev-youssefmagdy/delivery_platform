<?php

namespace App\DataTables\Admin\Markets;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Markets\CountryFilterData;
use App\Models\Country;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\EloquentDataTable;

/**
 * Countries listing (filters: search, active, currency, created range).
 */
final class CountriesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'countries-table';

    protected array $defaultOrder = [[1, 'asc']];

    public function __construct(private readonly CountryRepositoryInterface $countries)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<Country>
     */
    public function query(): Builder
    {
        return $this->countries->datatableQuery(CountryFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('iso2', __('admin.countries.fields.iso2'), orderable: true),
            $this->column('name', __('admin.countries.fields.name')),
            $this->column('phone_code', __('admin.countries.fields.phone_code')),
            $this->column('currency_code', __('admin.countries.fields.currency'), orderable: true),
            $this->column('timezone', __('admin.countries.fields.timezone'), orderable: true),
            $this->column('active', __('admin.countries.fields.active'), orderable: true),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $this->boolColumn($table, 'active');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, fn (Country $country): array => [
            TableAction::view(route('admin.countries.show', $country), $country),
            TableAction::edit(route('admin.countries.edit', $country), $country),
            TableAction::delete(route('admin.countries.destroy', $country), $country, self::TABLE_ID),
        ]);
    }
}
