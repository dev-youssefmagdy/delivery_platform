<?php

namespace App\DataTables\Admin\Support;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Support\MacroFilterData;
use App\Models\SupportMacro;
use App\Repositories\Contracts\Support\SupportMacroRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Macros (filters: title, category, active).
 */
final class SupportMacrosDataTable extends AdminDataTable
{
    public const TABLE_ID = 'support-macros-table';

    protected array $defaultOrder = [[0, 'asc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly SupportMacroRepositoryInterface $macroRepository)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<SupportMacro>
     */
    public function query(): Builder
    {
        return $this->macroRepository->datatableQuery(MacroFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('title_ar', __('admin.macros.fields.title').' (ar)', orderable: true, name: 'support_macros.title_ar'),
            $this->column('title_en', __('admin.macros.fields.title').' (en)'),
            $this->column('category', __('admin.macros.fields.category'), orderable: true, name: 'support_macros.category'),
            $this->column('active', __('admin.macros.fields.active'), orderable: true, name: 'support_macros.active'),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true, name: 'support_macros.updated_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['title_ar', 'title_en', 'category', 'active', 'updated_at', 'actions']);
        $table->editColumn('title_ar', static fn (Model $row): string => $row instanceof SupportMacro ? '<a href="'.e(route('admin.macros.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e($row->title_ar).'</a>' : '');
        $this->rawColumn('title_ar');
        $table->editColumn('title_en', static fn (Model $row): string => $row instanceof SupportMacro ? e($row->title_en ?? '—') : '');
        $table->editColumn('category', static fn (Model $row): string => $row instanceof SupportMacro ? e($row->category?->label() ?? __('admin.macros.all_categories')) : '');
        $this->boolColumn($table, 'active');
        $this->dateColumn($table, 'updated_at');

        $this->actionsColumn($table, static fn (SupportMacro $macro): array => [
            TableAction::view(route('admin.macros.show', $macro), $macro),
            TableAction::edit(route('admin.macros.edit', $macro), $macro),
            $macro->active
                ? TableAction::confirm(__('admin.macros.actions.deactivate'), route('admin.macros.deactivate', $macro), 'toggle', $macro, icon: 'x-circle', variant: 'danger', confirm: ['title' => __('admin.macros.confirm.deactivate_title'), 'message' => __('admin.macros.confirm.deactivate_message'), 'confirm_label' => __('admin.macros.actions.deactivate')], reloadTable: self::TABLE_ID)
                : TableAction::confirm(__('admin.macros.actions.activate'), route('admin.macros.activate', $macro), 'toggle', $macro, icon: 'check', variant: 'primary', confirm: ['title' => __('admin.macros.confirm.activate_title'), 'message' => __('admin.macros.confirm.activate_message'), 'confirm_label' => __('admin.macros.actions.activate')], reloadTable: self::TABLE_ID),
        ]);
    }
}
