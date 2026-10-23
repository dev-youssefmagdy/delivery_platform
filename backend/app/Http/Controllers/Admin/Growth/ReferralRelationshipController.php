<?php

namespace App\Http\Controllers\Admin\Growth;

use App\DataTables\Admin\Growth\ReferralRelationshipsDataTable;
use App\Enums\Growth\FraudFlag;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Growth\ReviewDecisionRequest;
use App\Models\ReferralRelationship;
use App\Services\Audit\AuditTrailService;
use App\Services\Growth\ReferralReviewService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Referral relationships (referrals.view) and the review of flagged ones (referrals.review).
 */
final class ReferralRelationshipController extends AdminController
{
    public function __construct(private readonly ReferralReviewService $reviews) {}

    public function index(ReferralRelationshipsDataTable $table): mixed
    {
        return $table->render('admin.growth.referral-relationships.index');
    }

    public function show(ReferralRelationship $referralRelationship, AuditTrailService $trail): View
    {
        return view('admin.growth.referral-relationships.show', [
            'relationship' => $referralRelationship->loadMissing(['campaign', 'referrer:id,uuid,name', 'referee:id,uuid,name', 'rewards']),
            'flags' => collect($referralRelationship->fraud_flags ?? [])->map(static fn (array $flag): array => ['label' => FraudFlag::tryFrom($flag['flag'])?->label() ?? $flag['flag'], 'evidence' => $flag['evidence']]),
            'logs' => $trail->latestFor($referralRelationship),
        ]);
    }

    public function approve(ReviewDecisionRequest $request, ReferralRelationship $referralRelationship): AdminFormResponse
    {
        $this->reviews->approve($referralRelationship, $this->actor(), $request->reason());

        return $this->formResponse(__('growth.referral.approved'))->reload();
    }

    public function reject(ReviewDecisionRequest $request, ReferralRelationship $referralRelationship): AdminFormResponse
    {
        $this->reviews->reject($referralRelationship, $this->actor(), (string) $request->reason());

        return $this->formResponse(__('growth.referral.rejected'))->reload();
    }
}
