<?php

namespace App\Http\Controllers\Admin\Finance;

use App\DataTables\Admin\Finance\CommissionRulesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Finance\ActivateCommissionRuleRequest;
use App\Http\Requests\Admin\Finance\DeactivateCommissionRuleRequest;
use App\Http\Requests\Admin\Finance\PreviewCommissionRuleRequest;
use App\Http\Requests\Admin\Finance\StoreCommissionRuleRequest;
use App\Http\Requests\Admin\Finance\UpdateCommissionRuleRequest;
use App\Models\CommissionRule;
use App\Repositories\Contracts\Finance\CommissionRuleRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Finance\CommissionRuleService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Commission rules under "Pricing" (commission_rules.manage): who pays the platform how much, by service, zone, tier and
 * campaign, with versions and a read-only calculator. Rules are disabled, never deleted.
 */
final class CommissionRuleController extends AdminController
{
    public function __construct(private readonly CommissionRuleService $service) {}

    public function index(CommissionRulesDataTable $table): mixed
    {
        return $table->render('admin.commission-rules.index');
    }

    public function create(): View
    {
        return view('admin.commission-rules.create');
    }

    public function store(StoreCommissionRuleRequest $request): AdminFormResponse
    {
        $rule = $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.commission_rules.created'))->redirect(route('admin.commission-rules.show', $rule));
    }

    public function show(CommissionRule $commissionRule, CommissionRuleRepositoryInterface $rules): View
    {
        return view('admin.commission-rules.show', [
            'rule' => $commissionRule->load(['serviceCategory', 'serviceZone', 'currentVersion', 'versions']),
            'bookingCount' => $rules->bookingCount($commissionRule),
        ]);
    }

    public function activity(CommissionRule $commissionRule, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($commissionRule)]);
    }

    public function edit(CommissionRule $commissionRule): View
    {
        return view('admin.commission-rules.edit', ['rule' => $commissionRule->load(['serviceCategory', 'serviceZone', 'currentVersion'])]);
    }

    public function update(UpdateCommissionRuleRequest $request, CommissionRule $commissionRule): AdminFormResponse
    {
        $this->service->update($commissionRule, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.commission_rules.updated'))->redirect(route('admin.commission-rules.show', $commissionRule));
    }

    public function preview(PreviewCommissionRuleRequest $request, CommissionRule $commissionRule): AdminFormResponse
    {
        $commissionRule->load('currentVersion');
        $breakdown = $this->service->preview($commissionRule, $request->fareMinor(), $request->tipMinor());

        return $this->formResponse()->replaceHtml('#commission-preview-result', view('admin.commission-rules._preview-result', ['breakdown' => $breakdown])->render());
    }

    public function activate(ActivateCommissionRuleRequest $request, CommissionRule $commissionRule): AdminFormResponse
    {
        $this->service->setActive($commissionRule, true, null, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.commission_rules.activated'), route('admin.commission-rules.show', $commissionRule));
    }

    public function deactivate(DeactivateCommissionRuleRequest $request, CommissionRule $commissionRule): AdminFormResponse
    {
        $this->service->setActive($commissionRule, false, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.commission_rules.deactivated'), route('admin.commission-rules.show', $commissionRule));
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(CommissionRulesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
