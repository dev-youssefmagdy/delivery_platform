<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentAppLink;
use App\Repositories\Contracts\Content\ContentAppLinkRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

final class ContentAppLinksDataTable extends AdminDataTable
{
    public const TABLE_ID = 'content-app-links-table';

    public function __construct(private readonly ContentAppLinkRepositoryInterface $items)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentAppLink>
     */
    public function query(): Builder
    {
        return $this->items->datatableQuery(ContentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('app', __('content.fields.app'), orderable: true),
            $this->column('platform', __('content.fields.platform'), orderable: true),
            $this->column('url', __('content.fields.store_url')),
            $this->column('sort_order', __('content.fields.sort_order'), orderable: true),
            $this->column('active', __('content.fields.active'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->editColumn('app', static fn (Model $row): string => $row instanceof ContentAppLink ? $row->app->label() : '');
        $table->editColumn('platform', static fn (Model $row): string => $row instanceof ContentAppLink ? $row->platform->label() : '');
        $this->boolColumn($table, 'active');
        $this->actionsColumn($table, fn (ContentAppLink $item): array => [
            TableAction::edit(route('admin.content-app-links.edit', $item), $item),
            TableAction::delete(route('admin.content-app-links.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
