<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\DataTables\Admin\Reviews\ReviewAppealsDataTable;
use App\DataTables\Admin\Reviews\ReviewReportsDataTable;
use App\DataTables\Admin\Reviews\ReviewsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Reviews\DecideAppealRequest;
use App\Http\Requests\Admin\Reviews\DismissReportRequest;
use App\Http\Requests\Admin\Reviews\HideReviewRequest;
use App\Http\Requests\Admin\Reviews\RestoreReviewRequest;
use App\Http\Requests\Admin\Reviews\UnderReviewRequest;
use App\Models\BookingRating;
use App\Models\RatingAppeal;
use App\Models\RatingReport;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Services\Reviews\RatingAppealService;
use App\Services\Reviews\RatingModerationService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Reviews and moderation (reviews.view / reviews.moderate, SRS §16.12). Thin: the route `can:` and the FormRequest authorize through the
 * policies, one service call does the work and audits it. There is no action that edits a review: hide, restore or mark under review
 * (each with a reason), dismiss a report, decide an appeal.
 */
final class ReviewController extends AdminController
{
    public function __construct(
        private readonly RatingModerationService $moderation,
        private readonly RatingAppealService $appeals,
        private readonly RatingModerationRepositoryInterface $rows,
    ) {}

    public function index(ReviewsDataTable $table): mixed
    {
        return $table->render('admin.reviews.index', ['openReports' => $this->rows->countOpenReports(), 'openAppeals' => $this->rows->countOpenAppeals()]);
    }

    public function show(BookingRating $rating): View
    {
        $rating->load('booking:id,uuid,code,status');

        return view('admin.reviews.show', [
            'rating' => $rating,
            'raterName' => $this->rows->partyName($rating->rater_type, $rating->rater_id),
            'rateeName' => $this->rows->partyName($rating->ratee_type, $rating->ratee_id),
            'actions' => $this->rows->actionsOf($rating->id),
            'reports' => $this->rows->reportsOf($rating->id),
            'appeals' => $this->rows->appealsOf($rating->id),
        ]);
    }

    public function hide(HideReviewRequest $request, BookingRating $rating): AdminFormResponse
    {
        $this->moderation->hide($rating, $this->actor(), (string) $request->reason());

        return $this->done(__('reputation.admin.done.hidden'), $rating);
    }

    public function restore(RestoreReviewRequest $request, BookingRating $rating): AdminFormResponse
    {
        $this->moderation->restore($rating, $this->actor(), (string) $request->reason());

        return $this->done(__('reputation.admin.done.restored'), $rating);
    }

    public function underReview(UnderReviewRequest $request, BookingRating $rating): AdminFormResponse
    {
        $this->moderation->markUnderReview($rating, $this->actor(), (string) $request->reason());

        return $this->done(__('reputation.admin.done.under_review'), $rating);
    }

    public function dismissReport(DismissReportRequest $request, BookingRating $rating, RatingReport $report): AdminFormResponse
    {
        $this->moderation->dismissReport($report, $this->actor(), (string) $request->reason());

        return $this->formResponse(__('reputation.admin.done.report_dismissed'))->reloadTable(ReviewReportsDataTable::TABLE_ID)->reload();
    }

    public function decideAppeal(DecideAppealRequest $request, BookingRating $rating, RatingAppeal $appeal): AdminFormResponse
    {
        $this->appeals->decide($appeal, $this->actor(), $request->uphold(), (string) $request->reason());

        return $this->formResponse(__('reputation.admin.done.appeal_decided'))->reloadTable(ReviewAppealsDataTable::TABLE_ID)->reload();
    }

    private function done(string $message, BookingRating $rating): AdminFormResponse
    {
        return $this->formResponse($message)->reloadTable(ReviewsDataTable::TABLE_ID)->reload();
    }
}
