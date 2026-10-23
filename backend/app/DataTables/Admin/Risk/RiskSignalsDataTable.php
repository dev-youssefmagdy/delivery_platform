<?php

namespace App\DataTables\Admin\Risk;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Risk\RiskSignalFilterData;
use App\Enums\Risk\RiskSubjectType;
use App\Models\RiskSignal;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Services\Risk\RiskSubjectResolver;
use App\Support\Admin\Tables\TableAction;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * Risk signals queue (filters: type, status, subject type, minimum score, detected range). The default view is the active queue,
 * highest score first. Row shortcuts: open the customer or provider, view the evidence, and the four actions (each asks for a reason).
 * Signals are internal: nothing here is ever sent to an app.
 */
final class RiskSignalsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'risk-signals-table';

    protected array $defaultOrder = [[2, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(
        private readonly RiskSignalRepositoryInterface $signals,
        private readonly RiskSubjectResolver $subjects,
    ) {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<RiskSignal>
     */
    public function query(): Builder
    {
        return $this->signals->datatableQuery(RiskSignalFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('type', __('reputation.admin.fields.type'), orderable: true, name: 'risk_signals.type'),
            $this->column('subject', __('reputation.admin.fields.subject')),
            $this->column('score', __('reputation.admin.fields.score'), orderable: true, name: 'risk_signals.score'),
            $this->column('status', __('reputation.admin.fields.status'), orderable: true, name: 'risk_signals.status'),
            $this->column('first_detected_at', __('reputation.admin.fields.detected'), orderable: true, name: 'risk_signals.first_detected_at'),
            $this->column('last_evaluated_at', __('reputation.admin.fields.evaluated'), orderable: true, name: 'risk_signals.last_evaluated_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['type', 'subject', 'score', 'status', 'first_detected_at', 'last_evaluated_at', 'actions']);

        $table->editColumn('type', static fn (Model $row): string => $row instanceof RiskSignal ? '<a href="'.e(route('admin.risk-signals.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e($row->type->label()).'</a>' : '');
        $this->rawColumn('type');
        $table->addColumn('subject', function (RiskSignal $signal): string {
            $subject = $this->subjects->describe($signal);
            $name = $subject['url'] === null ? e($subject['name']) : '<a href="'.e($subject['url']).'" class="text-brand-700 hover:underline">'.e($subject['name']).'</a>';

            return $name.' '.Blade::renderComponent(new Badge($signal->subject_type));
        });
        $this->rawColumn('subject');
        $table->editColumn('score', static fn (Model $row): string => $row instanceof RiskSignal ? e((string) $row->score).'/100' : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'first_detected_at');
        $this->dateColumn($table, 'last_evaluated_at');

        $this->actionsColumn($table, function (RiskSignal $signal): array {
            $subject = $this->subjects->describe($signal);
            $actions = [TableAction::view(route('admin.risk-signals.show', $signal), $signal)];

            if ($subject['model'] !== null && $subject['url'] !== null) {
                $actions[] = new TableAction($signal->subject_type === RiskSubjectType::Provider ? __('reputation.admin.open_provider') : __('reputation.admin.open_customer'), $subject['url'], icon: 'user', ability: 'view', subject: $subject['model']);
            }

            foreach (['restrict' => ['lock-closed', 'warning'], 'suspend' => ['pause', 'danger'], 'escalate' => ['arrow-up', 'warning'], 'dismiss' => ['x-mark', 'neutral']] as $ability => [$icon, $variant]) {
                $actions[] = TableAction::confirm(
                    __('reputation.enums.risk_action.'.$ability),
                    route('admin.risk-signals.'.$ability, $signal),
                    $ability,
                    $signal,
                    icon: $icon,
                    variant: $variant,
                    reasonRequired: true,
                    confirm: ['title' => __('reputation.admin.confirm.'.$ability.'_title'), 'message' => __('reputation.admin.confirm.'.$ability.'_message'), 'confirm_label' => __('reputation.enums.risk_action.'.$ability)],
                    reloadTable: self::TABLE_ID,
                );
            }

            return $actions;
        });
    }
}
