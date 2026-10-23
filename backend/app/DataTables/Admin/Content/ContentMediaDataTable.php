<?php

namespace App\DataTables\Admin\Content;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Content\ContentFilterData;
use App\Models\ContentMedia;
use App\Repositories\Contracts\Content\ContentMediaRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Media library listing (filters: name or alt text, uploaded range).
 */
final class ContentMediaDataTable extends AdminDataTable
{
    public const TABLE_ID = 'content-media-table';

    public function __construct(private readonly ContentMediaRepositoryInterface $media)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<ContentMedia>
     */
    public function query(): Builder
    {
        return $this->media->datatableQuery(ContentFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('preview', __('admin.content.fields.preview')),
            $this->column('original_name', __('admin.content.fields.file'), orderable: true),
            $this->column('dimensions', __('admin.content.fields.dimensions')),
            $this->column('size_bytes', __('admin.content.fields.size'), orderable: true),
            $this->column('created_at', __('admin.common.created_at'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->addColumn('preview', static fn (Model $row): string => $row instanceof ContentMedia
            ? '<img src="'.e($row->url(320)).'" alt="'.e((string) $row->getTranslation('alt', app()->getLocale(), true)).'" class="size-12 rounded-(--radius-control) border border-slate-200 object-cover" loading="lazy">'
            : '');
        $this->rawColumn('preview');
        $table->addColumn('dimensions', static fn (Model $row): string => $row instanceof ContentMedia ? $row->width.' × '.$row->height : '');
        $table->editColumn('size_bytes', static fn (Model $row): string => $row instanceof ContentMedia ? number_format($row->size_bytes / 1024, 0).' KB' : '');
        $this->dateColumn($table, 'created_at');
        $this->actionsColumn($table, fn (ContentMedia $item): array => [
            TableAction::edit(route('admin.content-media.edit', $item), $item),
            TableAction::delete(route('admin.content-media.destroy', $item), $item, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
