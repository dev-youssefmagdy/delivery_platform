<?php

namespace App\DataTables\Admin\Provider;

use App\DataTables\Admin\AdminDataTable;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Repositories\Contracts\Provider\DocumentRequirementRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use LogicException;
use Yajra\DataTables\EloquentDataTable;

/**
 * Requirements tab of a document type: where it is mandatory or optional. Each empty scope reads "everyone / any".
 */
final class DocumentRequirementsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'document-requirements-table';

    protected bool $globalSearch = false;

    public function __construct(private readonly DocumentRequirementRepositoryInterface $requirements)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<DocumentRequirement>
     */
    public function query(): Builder
    {
        return $this->requirements->datatableQuery($this->type());
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true),
            $this->column('country', __('admin.document_types.fields.country')),
            $this->column('city', __('admin.document_types.fields.city')),
            $this->column('service_category', __('admin.document_types.fields.service_category')),
            $this->column('vehicle_category', __('admin.document_types.fields.vehicle_category')),
            $this->column('provider_type', __('admin.document_types.fields.provider_type')),
            $this->column('mandatory', __('admin.document_types.fields.mandatory'), orderable: true),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $any = '<span class="text-slate-400">'.e((string) __('admin.document_types.any')).'</span>';
        $table->only(['id', 'country', 'city', 'service_category', 'vehicle_category', 'provider_type', 'mandatory', 'actions']);

        foreach (['country' => 'country', 'city' => 'city', 'service_category' => 'serviceCategory', 'vehicle_category' => 'vehicleCategory'] as $column => $relation) {
            $table->addColumn($column, static function (Model $row) use ($relation, $any): string {
                $related = $row instanceof DocumentRequirement ? $row->{$relation} : null;

                return $related === null ? $any : e((string) $related->name);
            });
            $this->rawColumn($column);
        }

        $table->addColumn('provider_type', static fn (Model $row): string => $row instanceof DocumentRequirement && $row->provider_type !== null ? e($row->provider_type->label()) : $any);
        $this->rawColumn('provider_type');
        $table->editColumn('mandatory', static fn (Model $row): string => Blade::renderComponent(new Badge(
            label: $row->getAttribute('mandatory') ? __('admin.document_types.mandatory') : __('admin.document_types.optional'),
            color: $row->getAttribute('mandatory') ? 'danger' : 'neutral',
        )));
        $this->rawColumn('mandatory');

        $type = $this->type();

        $this->actionsColumn($table, static fn (DocumentRequirement $requirement): array => [
            TableAction::confirm(
                __('admin.document_types.actions.remove_requirement'),
                route('admin.document-types.requirements.destroy', ['documentType' => $type, 'requirement' => $requirement->id]),
                'update',
                $type,
                icon: 'trash',
                method: 'DELETE',
                reasonRequired: true,
                confirm: ['title' => __('admin.document_types.confirm.remove_requirement_title'), 'message' => __('admin.document_types.confirm.remove_requirement_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }

    private function type(): DocumentType
    {
        $type = $this->request()->route('documentType');

        return $type instanceof DocumentType ? $type : throw new LogicException('The requirements table needs a {documentType} route parameter.');
    }
}
