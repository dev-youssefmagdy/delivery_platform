<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Provider\VerificationQueueDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Provider\ApproveApplicationRequest;
use App\Http\Requests\Admin\Provider\ClaimProviderRequest;
use App\Http\Requests\Admin\Provider\RejectApplicationRequest;
use App\Http\Requests\Admin\Provider\RequestMoreInformationRequest;
use App\Models\ProviderProfile;
use App\Services\Provider\ProviderOnboardingService;
use App\Services\Provider\VerificationReviewService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * Verification queue and the side-by-side review screen: documents on the left (signed, audited access), checklist and
 * decision on the right. Approve, request more information and reject all need their own permission, and the last two
 * need a reason; every decision is audited.
 */
final class VerificationController extends AdminController
{
    public function __construct(
        private readonly ProviderOnboardingService $onboarding,
        private readonly VerificationReviewService $reviews,
    ) {}

    public function index(VerificationQueueDataTable $table): mixed
    {
        return $table->render('admin.verification.index');
    }

    public function review(ProviderProfile $provider): View
    {
        return view('admin.verification.review', ['review' => $this->reviews->build($provider, $this->actor(), Gate::allows('viewPii', $provider))]);
    }

    public function claim(ClaimProviderRequest $request, ProviderProfile $provider): AdminFormResponse
    {
        $this->onboarding->assignToMe($provider, $this->actor());

        return $this->formResponse(__('admin.verification.claimed'))->reloadTable(VerificationQueueDataTable::TABLE_ID);
    }

    public function approve(ApproveApplicationRequest $request, ProviderProfile $provider): AdminFormResponse
    {
        $this->onboarding->approve($provider, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.verification.approved'))->redirect(route('admin.verification.index'));
    }

    public function requestMoreInformation(RequestMoreInformationRequest $request, ProviderProfile $provider): AdminFormResponse
    {
        $this->onboarding->requestMoreInformation($provider, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.verification.more_info_requested'))->redirect(route('admin.verification.index'));
    }

    public function reject(RejectApplicationRequest $request, ProviderProfile $provider): AdminFormResponse
    {
        $this->onboarding->reject($provider, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.verification.rejected'))->redirect(route('admin.verification.index'));
    }
}
