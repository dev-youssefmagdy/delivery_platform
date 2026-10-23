<?php

namespace App\DataTables\Admin\Risk;

use App\Models\RiskSignal;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Columns shared by the "Risk" tabs of the customer and provider pages (type, score, status, detected, view).
 *
 * @mixin \App\DataTables\Admin\AdminDataTable
 */
trait RendersRiskSignalColumns
{
    /**
     * @return list<\Yajra\DataTables\Html\Column>
     */
    protected function riskColumns(): array
    {
        return [
            $this->column('type', __('reputation.admin.fields.type'), orderable: true, name: 'risk_signals.type'),
            $this->column('score', __('reputation.admin.fields.score'), orderable: true, name: 'risk_signals.score'),
            $this->column('status', __('reputation.admin.fields.status'), orderable: true, name: 'risk_signals.status'),
            $this->column('first_detected_at', __('reputation.admin.fields.detected'), orderable: true, name: 'risk_signals.first_detected_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configureRisk(EloquentDataTable $table): void
    {
        $table->only(['type', 'score', 'status', 'first_detected_at', 'actions']);
        $table->editColumn('type', static fn (Model $row): string => $row instanceof RiskSignal ? e($row->type->label()) : '');
        $table->editColumn('score', static fn (Model $row): string => $row instanceof RiskSignal ? e((string) $row->score).'/100' : '');
        $this->enumBadgeColumn($table, 'status');
        $this->dateColumn($table, 'first_detected_at');
        $this->actionsColumn($table, static fn (RiskSignal $signal): array => [TableAction::view(route('admin.risk-signals.show', $signal), $signal)]);
    }
}
