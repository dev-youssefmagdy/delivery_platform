<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Content\Concerns\RendersLocales;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentMenu;
use App\Repositories\Contracts\Content\MenuRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * CMS menu listing (Appendix C filters: type, status, locale completeness, updated range).
 */
final class ContentMenusDataTable extends AdminDataTable
{
    use RendersLocales;

    public const TABLE_ID = 'content-menus-table';

    public function __construct(private readonly MenuRepositoryInterface $items)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentMenu>
     */
    public function query(): Builder
    {
        return $this->items->datatableQuery(ContentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('title', __('admin.content.fields.title')),
            $this->column('location', __('admin.content.fields.location'), orderable: true),
            $this->column('status', __('admin.content.fields.status'), orderable: true),
            $this->column('locales', __('admin.content.fields.locales')),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'title');
        $table->editColumn('location', static fn (Model $row): string => $row instanceof ContentMenu ? $row->location->label() : '');
        $this->enumBadgeColumn($table, 'status');
        $this->localesColumn($table, 'title');
        $this->dateColumn($table, 'updated_at');
        $this->actionsColumn($table, fn (ContentMenu $item): array => [
            TableAction::view(route('admin.content-menus.show', $item), $item),
            TableAction::edit(route('admin.content-menus.edit', $item), $item),
            TableAction::delete(route('admin.content-menus.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
