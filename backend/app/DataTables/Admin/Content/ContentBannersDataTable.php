<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Content\Concerns\RendersLocales;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentBanner;
use App\Repositories\Contracts\Content\BannerRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * CMS banner listing (Appendix C filters: type, status, locale completeness, updated range).
 */
final class ContentBannersDataTable extends AdminDataTable
{
    use RendersLocales;

    public const TABLE_ID = 'content-banners-table';

    public function __construct(private readonly BannerRepositoryInterface $items)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentBanner>
     */
    public function query(): Builder
    {
        return $this->items->datatableQuery(ContentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('title', __('content.fields.title')),
            $this->column('placement', __('content.fields.placement'), orderable: true),
            $this->column('starts_at', __('content.fields.starts_at'), orderable: true),
            $this->column('status', __('content.fields.status'), orderable: true),
            $this->column('locales', __('content.fields.locales')),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'title');
        $table->editColumn('placement', static fn (Model $row): string => $row instanceof ContentBanner ? $row->placement->label() : '');
        $this->dateColumn($table, 'starts_at');
        $this->enumBadgeColumn($table, 'status');
        $this->localesColumn($table, 'title');
        $this->dateColumn($table, 'updated_at');
        $this->actionsColumn($table, fn (ContentBanner $item): array => [
            TableAction::view(route('admin.content-banners.show', $item), $item),
            TableAction::edit(route('admin.content-banners.edit', $item), $item),
            TableAction::delete(route('admin.content-banners.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
