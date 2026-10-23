<?php

namespace App\Http\Controllers\Admin\Growth;

use App\DataTables\Admin\Growth\IncentiveCampaignsDataTable;
use App\DataTables\Admin\Growth\IncentiveProgressDataTable;
use App\DTOs\Growth\IncentiveCampaignData;
use App\Enums\Growth\CampaignStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Growth\AdjustBudgetRequest;
use App\Http\Requests\Admin\Growth\SaveIncentiveCampaignRequest;
use App\Http\Requests\Admin\Growth\TransitionCampaignRequest;
use App\Models\IncentiveCampaign;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Growth\IncentiveCampaignService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Provider incentive campaigns (incentives.view / incentives.manage).
 */
final class IncentiveCampaignController extends AdminController
{
    public function __construct(private readonly IncentiveCampaignService $service, private readonly IncentiveRepositoryInterface $incentives) {}

    public function index(IncentiveCampaignsDataTable $table): mixed
    {
        return $table->render('admin.growth.incentive-campaigns.index');
    }

    public function create(): View
    {
        return view('admin.growth.incentive-campaigns.form', ['campaign' => null]);
    }

    public function store(SaveIncentiveCampaignRequest $request): AdminFormResponse
    {
        $campaign = $this->service->create($this->actor(), IncentiveCampaignData::fromRequest($request));

        return $this->formResponse(__('growth.saved'))->redirect(route('admin.incentive-campaigns.show', $campaign));
    }

    public function show(IncentiveCampaign $incentiveCampaign, IncentiveProgressDataTable $table, AuditTrailService $trail): mixed
    {
        return $table->render('admin.growth.incentive-campaigns.show', [
            'campaign' => $incentiveCampaign,
            'totals' => $this->incentives->campaignTotals($incentiveCampaign->id),
            'logs' => $trail->latestFor($incentiveCampaign),
        ]);
    }

    public function edit(IncentiveCampaign $incentiveCampaign): View
    {
        return view('admin.growth.incentive-campaigns.form', ['campaign' => $incentiveCampaign]);
    }

    public function update(SaveIncentiveCampaignRequest $request, IncentiveCampaign $incentiveCampaign): AdminFormResponse
    {
        $this->service->update($incentiveCampaign, $this->actor(), IncentiveCampaignData::fromRequest($request));

        return $this->formResponse(__('growth.saved'))->redirect(route('admin.incentive-campaigns.show', $incentiveCampaign));
    }

    public function transition(TransitionCampaignRequest $request, IncentiveCampaign $incentiveCampaign): AdminFormResponse
    {
        $this->service->transition($incentiveCampaign, $this->actor(), $request->target() ?? CampaignStatus::Draft, $request->reason());

        return $this->formResponse(__('growth.moved'))->reload();
    }

    public function adjust(AdjustBudgetRequest $request, IncentiveCampaign $incentiveCampaign): AdminFormResponse
    {
        $this->service->adjustBudget($incentiveCampaign, $this->actor(), (int) $request->validated('budget_minor'), (string) $request->reason());

        return $this->formResponse(__('growth.budget_adjusted'))->reload();
    }
}
