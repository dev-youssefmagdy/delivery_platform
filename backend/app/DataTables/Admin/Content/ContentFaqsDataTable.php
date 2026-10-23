<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DataTables\Admin\Content\Concerns\RendersLocales;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentFaq;
use App\Repositories\Contracts\Content\FaqRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * CMS faq listing (Appendix C filters: type, status, locale completeness, updated range).
 */
final class ContentFaqsDataTable extends AdminDataTable
{
    use RendersLocales;

    public const TABLE_ID = 'content-faqs-table';

    public function __construct(private readonly FaqRepositoryInterface $items)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentFaq>
     */
    public function query(): Builder
    {
        return $this->items->datatableQuery(ContentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('question', __('content.fields.question')),
            $this->column('sort_order', __('content.fields.sort_order'), orderable: true),
            $this->column('status', __('content.fields.status'), orderable: true),
            $this->column('locales', __('content.fields.locales')),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'question');
        $this->enumBadgeColumn($table, 'status');
        $this->localesColumn($table, 'question');
        $this->dateColumn($table, 'updated_at');
        $this->actionsColumn($table, fn (ContentFaq $item): array => [
            TableAction::view(route('admin.content-faqs.show', $item), $item),
            TableAction::edit(route('admin.content-faqs.edit', $item), $item),
            TableAction::delete(route('admin.content-faqs.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
