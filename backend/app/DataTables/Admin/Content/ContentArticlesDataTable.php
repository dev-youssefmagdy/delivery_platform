<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Content\Concerns\RendersLocales;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentArticle;
use App\Repositories\Contracts\Content\ArticleRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * CMS article listing (Appendix C filters: type, status, locale completeness, updated range).
 */
final class ContentArticlesDataTable extends AdminDataTable
{
    use RendersLocales;

    public const TABLE_ID = 'content-articles-table';

    public function __construct(private readonly ArticleRepositoryInterface $items)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentArticle>
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
            $this->column('slug', __('admin.content.fields.slug'), orderable: true),
            $this->column('type', __('admin.content.fields.article_type'), orderable: true),
            $this->column('status', __('admin.content.fields.status'), orderable: true),
            $this->column('locales', __('admin.content.fields.locales')),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'title');
        $table->editColumn('type', static fn (Model $row): string => $row instanceof ContentArticle ? $row->type->label() : '');
        $this->enumBadgeColumn($table, 'status');
        $this->localesColumn($table, 'title');
        $this->dateColumn($table, 'updated_at');
        $this->actionsColumn($table, fn (ContentArticle $item): array => [
            TableAction::view(route('admin.content-articles.show', $item), $item),
            TableAction::edit(route('admin.content-articles.edit', $item), $item),
            TableAction::delete(route('admin.content-articles.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
