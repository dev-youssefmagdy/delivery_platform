<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Content\Concerns\RendersLocales;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentCategory;
use App\Repositories\Contracts\Content\ContentCategoryRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

final class ContentCategoriesDataTable extends AdminDataTable
{
    use RendersLocales;

    public const TABLE_ID = 'content-categories-table';

    public function __construct(private readonly ContentCategoryRepositoryInterface $items)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentCategory>
     */
    public function query(): Builder
    {
        return $this->items->datatableQuery(ContentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('name', __('content.fields.name')),
            $this->column('kind', __('content.fields.category_kind'), orderable: true),
            $this->column('slug', __('content.fields.slug'), orderable: true),
            $this->column('sort_order', __('content.fields.sort_order'), orderable: true),
            $this->column('active', __('content.fields.active'), orderable: true),
            $this->column('locales', __('content.fields.locales')),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $table->editColumn('kind', static fn (Model $row): string => $row instanceof ContentCategory ? $row->kind->label() : '');
        $this->boolColumn($table, 'active');
        $this->localesColumn($table, 'name');
        $this->actionsColumn($table, fn (ContentCategory $item): array => [
            TableAction::edit(route('admin.content-categories.edit', $item), $item),
            TableAction::delete(route('admin.content-categories.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
