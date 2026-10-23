<?php

namespace App\Http\Controllers\Admin\Risk;

use App\DataTables\Admin\Risk\RiskSignalsDataTable;
use App\Enums\Risk\RiskAction;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Risk\DismissSignalRequest;
use App\Http\Requests\Admin\Risk\EscalateSignalRequest;
use App\Http\Requests\Admin\Risk\RestrictSignalSubjectRequest;
use App\Http\Requests\Admin\Risk\SuspendSignalSubjectRequest;
use App\Models\RiskSignal;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Risk\RiskSignalActionService;
use App\Services\Risk\RiskSubjectResolver;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Risk signals queue (risk.view / risk.manage, SRS §15). Signals are internal review tasks: the queue, the evidence page and four actions
 * (suspend, restrict, dismiss, escalate), each with a mandatory reason and an audit entry, through the existing account services.
 */
final class RiskSignalController extends AdminController
{
    public function __construct(
        private readonly RiskSignalActionService $actions,
        private readonly RiskSignalRepositoryInterface $signals,
        private readonly RiskSubjectResolver $subjects,
    ) {}

    public function index(RiskSignalsDataTable $table): mixed
    {
        return $table->render('admin.risk-signals.index', ['active' => $this->signals->countActive()]);
    }

    public function show(RiskSignal $signal, AuditTrailService $trail): View
    {
        return view('admin.risk-signals.show', ['signal' => $signal, 'subject' => $this->subjects->describe($signal), 'logs' => $trail->latestFor($signal)]);
    }

    public function suspend(SuspendSignalSubjectRequest $request, RiskSignal $signal): AdminFormResponse
    {
        $this->actions->act($signal, $this->actor(), RiskAction::Suspend, (string) $request->reason(), $request->expiresAt());

        return $this->done(__('reputation.admin.done.suspended'), $signal);
    }

    public function restrict(RestrictSignalSubjectRequest $request, RiskSignal $signal): AdminFormResponse
    {
        $this->actions->act($signal, $this->actor(), RiskAction::Restrict, (string) $request->reason());

        return $this->done(__('reputation.admin.done.restricted'), $signal);
    }

    public function dismiss(DismissSignalRequest $request, RiskSignal $signal): AdminFormResponse
    {
        $this->actions->act($signal, $this->actor(), RiskAction::Dismiss, (string) $request->reason());

        return $this->done(__('reputation.admin.done.dismissed'), $signal);
    }

    public function escalate(EscalateSignalRequest $request, RiskSignal $signal): AdminFormResponse
    {
        $this->actions->act($signal, $this->actor(), RiskAction::Escalate, (string) $request->reason());

        return $this->done(__('reputation.admin.done.escalated'), $signal);
    }

    private function done(string $message, RiskSignal $signal): AdminFormResponse
    {
        return $this->formResponse($message)->reloadTable(RiskSignalsDataTable::TABLE_ID)->reload();
    }
}
