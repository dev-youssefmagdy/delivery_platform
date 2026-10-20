<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Provider\DocumentTypeFilterData;
use App\Models\DocumentType;
use App\Repositories\Contracts\Provider\DocumentTypeRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Document types listing (filters: search, applies to, active).
 */
final class DocumentTypesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'document-types-table';

    protected array $defaultOrder = [[3, 'asc']];

    public function __construct(private readonly DocumentTypeRepositoryInterface $types)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<DocumentType>
     */
    public function query(): Builder
    {
        return $this->types->datatableQuery(DocumentTypeFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('code', __('admin.catalog.fields.code'), orderable: true),
            $this->column('name', __('admin.document_types.fields.name')),
            $this->column('sort_order', __('admin.catalog.fields.sort_order'), orderable: true),
            $this->column('applies_to', __('admin.document_types.fields.applies_to'), orderable: true),
            $this->column('requires_expiry', __('admin.document_types.fields.requires_expiry')),
            $this->column('requirements_count', __('admin.document_types.fields.requirements')),
            $this->column('active', __('admin.catalog.fields.active'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $this->translatedColumn($table, 'name');
        $table->editColumn('applies_to', static fn (Model $row): string => $row instanceof DocumentType ? $row->applies_to->label() : '');
        $this->boolColumn($table, 'requires_expiry');
        $this->boolColumn($table, 'active');
        $this->actionsColumn($table, fn (DocumentType $type): array => [
            TableAction::view(route('admin.document-types.show', $type), $type),
            TableAction::edit(route('admin.document-types.edit', $type), $type),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.document-types.activate', $type),
                'activate',
                $type,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.catalog.confirm.activate_title'), 'message' => __('admin.document_types.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.document-types.deactivate', $type),
                'deactivate',
                $type,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.catalog.confirm.deactivate_title'), 'message' => __('admin.document_types.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::delete(route('admin.document-types.destroy', $type), $type, self::TABLE_ID, reasonRequired: true),
        ]);
    }
}
