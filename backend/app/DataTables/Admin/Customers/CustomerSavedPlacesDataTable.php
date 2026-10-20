<?php

namespace App\DataTables\Admin\Customers;

use App\Models\SavedPlace;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Saved places (home/work addresses): personal data, so the tab route requires `customers.view_pii`.
 */
final class CustomerSavedPlacesDataTable extends CustomerTabDataTable
{
    public const TABLE_ID = 'customer-saved-places-table';

    protected array $defaultOrder = [[4, 'desc']];

    public function __construct(private readonly SavedPlaceRepositoryInterface $places)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<SavedPlace>
     */
    public function query(): Builder
    {
        return $this->places->datatableQuery($this->customer()->id);
    }

    protected function columns(): array
    {
        return [
            $this->column('label', __('admin.customers.places.label')),
            $this->column('type', __('admin.customers.places.type'), orderable: true),
            $this->column('address', __('admin.customers.places.address')),
            $this->column('point', __('admin.customers.places.coordinates')),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['label', 'type', 'address', 'point', 'created_at']);
        $table->editColumn('type', static fn (Model $row): string => $row instanceof SavedPlace ? e($row->type->label()) : '');
        $table->addColumn('address', static fn (Model $row): string => $row instanceof SavedPlace ? e((string) ($row->address['line'] ?? '—')) : '');
        $table->editColumn('point', static fn (Model $row): string => $row instanceof SavedPlace
            ? '<span class="ltr-only">'.e(round($row->point->lat, 5).', '.round($row->point->lng, 5)).'</span>'
            : '');
        $this->dateColumn($table, 'created_at');
        $this->rawColumn('point');
    }
}
