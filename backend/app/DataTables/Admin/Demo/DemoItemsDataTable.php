<?php

namespace App\DataTables\Admin\Demo;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Demo\DemoItemFilterData;
use App\Enums\Admin\AdminPermission;
use App\Models\DemoItem;
use App\Repositories\Contracts\Demo\DemoItemRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Demo Item listing: every column helper (translation, enum badges, money, relation, boolean, date) and every
 * row action kind (link, drawer, confirm, confirm with mandatory reason).
 */
final class DemoItemsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'demo-items-table';

    public function __construct(private readonly DemoItemRepositoryInterface $demoItems)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<DemoItem>
     */
    public function query(): Builder
    {
        return $this->demoItems->datatableQuery(DemoItemFilterData::fromRequest($this->filterRequest()));
    }

    public function exportPermission(): AdminPermission
    {
        return AdminPermission::DemoItemsExport;
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('admin.demo_items.fields.name')),
            $this->column('code', __('admin.demo_items.fields.code'), orderable: true, searchable: true),
            $this->column('type', __('admin.demo_items.fields.type'), orderable: true),
            $this->column('status', __('admin.demo_items.fields.status'), orderable: true),
            $this->column('price', __('admin.demo_items.fields.price'), orderable: true, name: 'price_minor'),
            $this->column('country', __('admin.demo_items.fields.country'), name: 'country_id'),
            $this->column('is_featured', __('admin.demo_items.fields.is_featured'), orderable: true),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $this->enumBadgeColumn($table, 'type');
        $this->enumBadgeColumn($table, 'status');
        $this->moneyColumn($table, 'price');
        $table->editColumn('country', static fn (DemoItem $demoItem): string => $demoItem->country === null ? '—' : $demoItem->country->name);
        $this->boolColumn($table, 'is_featured');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, fn (DemoItem $demoItem): array => [
            TableAction::quickView(route('admin.demo-items.quick-view', $demoItem), $demoItem),
            TableAction::view(route('admin.demo-items.show', $demoItem), $demoItem),
            TableAction::edit(route('admin.demo-items.edit', $demoItem), $demoItem),
            TableAction::confirm(
                __('admin.demo_items.archive'),
                route('admin.demo-items.archive', $demoItem),
                'archive',
                $demoItem,
                icon: 'archive',
                variant: 'warning',
                reasonRequired: true,
                confirm: ['title' => __('admin.demo_items.archive_title'), 'message' => __('admin.demo_items.archive_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.demo-items.destroy', $demoItem), $demoItem, self::TABLE_ID),
        ]);
    }

    /**
     * CSV row: plain values (Latin digits) rather than the HTML cells.
     *
     * @return list<string>
     */
    public function exportRow(Model $row): array
    {
        if (! $row instanceof DemoItem) {
            return parent::exportRow($row);
        }

        return [
            (string) $row->id,
            $row->getTranslation('name', app()->getLocale(), true),
            $row->code,
            $row->type->label(),
            $row->status->label(),
            $this->stringify($row->price, latinDigits: true),
            $row->country === null ? '' : $row->country->name,
            $this->stringify($row->is_featured),
            $this->stringify($row->created_at),
        ];
    }
}
