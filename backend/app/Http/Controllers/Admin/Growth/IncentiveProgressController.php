<?php

namespace App\Http\Controllers\Admin\Growth;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Growth\ReviewDecisionRequest;
use App\Models\IncentiveProgress;
use App\Services\Growth\IncentiveReviewService;
use App\Support\Http\AdminFormResponse;

/**
 * Decision on a held quest progress (incentives.review).
 */
final class IncentiveProgressController extends AdminController
{
    public function __construct(private readonly IncentiveReviewService $reviews) {}

    public function release(ReviewDecisionRequest $request, IncentiveProgress $incentiveProgress): AdminFormResponse
    {
        $this->reviews->release($incentiveProgress, $this->actor(), $request->reason());

        return $this->formResponse(__('growth.incentive.released'))->reloadTable('incentive-progress-table');
    }

    public function reject(ReviewDecisionRequest $request, IncentiveProgress $incentiveProgress): AdminFormResponse
    {
        $this->reviews->reject($incentiveProgress, $this->actor(), (string) $request->reason());

        return $this->formResponse(__('growth.incentive.rejected'))->reloadTable('incentive-progress-table');
    }
}
