<?php

namespace App\Http\Controllers\Admin\Growth;

use App\DataTables\Admin\Growth\ReferralCampaignsDataTable;
use App\DTOs\Growth\ReferralCampaignData;
use App\Enums\Growth\CampaignStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Growth\AdjustBudgetRequest;
use App\Http\Requests\Admin\Growth\SaveReferralCampaignRequest;
use App\Http\Requests\Admin\Growth\TransitionCampaignRequest;
use App\Models\ReferralCampaign;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Growth\ReferralCampaignService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Referral campaigns (referrals.view / referrals.manage).
 */
final class ReferralCampaignController extends AdminController
{
    public function __construct(private readonly ReferralCampaignService $service, private readonly ReferralRepositoryInterface $referrals) {}

    public function index(ReferralCampaignsDataTable $table): mixed
    {
        return $table->render('admin.growth.referral-campaigns.index');
    }

    public function create(): View
    {
        return view('admin.growth.referral-campaigns.form', ['campaign' => null]);
    }

    public function store(SaveReferralCampaignRequest $request): AdminFormResponse
    {
        $campaign = $this->service->create($this->actor(), ReferralCampaignData::fromRequest($request));

        return $this->formResponse(__('growth.saved'))->redirect(route('admin.referral-campaigns.show', $campaign));
    }

    public function show(ReferralCampaign $referralCampaign, AuditTrailService $trail): View
    {
        return view('admin.growth.referral-campaigns.show', [
            'campaign' => $referralCampaign,
            'totals' => $this->referrals->campaignTotals($referralCampaign->id),
            'logs' => $trail->latestFor($referralCampaign),
        ]);
    }

    public function edit(ReferralCampaign $referralCampaign): View
    {
        return view('admin.growth.referral-campaigns.form', ['campaign' => $referralCampaign]);
    }

    public function update(SaveReferralCampaignRequest $request, ReferralCampaign $referralCampaign): AdminFormResponse
    {
        $this->service->update($referralCampaign, $this->actor(), ReferralCampaignData::fromRequest($request));

        return $this->formResponse(__('growth.saved'))->redirect(route('admin.referral-campaigns.show', $referralCampaign));
    }

    public function transition(TransitionCampaignRequest $request, ReferralCampaign $referralCampaign): AdminFormResponse
    {
        $this->service->transition($referralCampaign, $this->actor(), $request->target() ?? CampaignStatus::Draft, $request->reason());

        return $this->formResponse(__('growth.moved'))->reload();
    }

    public function adjust(AdjustBudgetRequest $request, ReferralCampaign $referralCampaign): AdminFormResponse
    {
        $this->service->adjustBudget($referralCampaign, $this->actor(), (int) $request->validated('budget_minor'), (string) $request->reason());

        return $this->formResponse(__('growth.budget_adjusted'))->reload();
    }
}
