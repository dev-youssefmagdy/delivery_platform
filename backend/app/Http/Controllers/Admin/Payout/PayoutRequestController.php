<?php

namespace App\Http\Controllers\Admin\Payout;

use App\DataTables\Admin\Payout\PayoutRequestsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payout\ApprovePayoutRequest;
use App\Http\Requests\Admin\Payout\MarkPayoutFailedRequest;
use App\Http\Requests\Admin\Payout\MarkPayoutPaidRequest;
use App\Http\Requests\Admin\Payout\MarkPayoutProcessingRequest;
use App\Http\Requests\Admin\Payout\RejectPayoutRequest;
use App\Http\Requests\Admin\Payout\ReversePayoutRequest;
use App\Http\Requests\Admin\Payout\StartPayoutReviewRequest;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Payout\PayoutEventRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Payout\PayoutReviewService;
use App\Services\Payout\PayoutSettlementService;
use App\Support\Files\PrivateFileStorage;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payout requests (payouts.view / payouts.review / payouts.approve): the queue, one request with its timeline, and every step of
 * its life. The money moves in the ledger inside PayoutReviewService / PayoutSettlementService; these actions only authorize
 * (FormRequest -> policy), pass the actor and the mandatory reason / reference, and answer.
 */
final class PayoutRequestController extends AdminController
{
    public function __construct(
        private readonly PayoutReviewService $review,
        private readonly PayoutSettlementService $settlement,
    ) {}

    public function index(PayoutRequestsDataTable $table): mixed
    {
        return $table->render('admin.payout-requests.index');
    }

    public function show(PayoutRequest $payoutRequest, AuditTrailService $trail, PayoutEventRepositoryInterface $events): View
    {
        $payoutRequest->load(['provider.user', 'method', 'batch', 'reviewedBy', 'firstApprovedBy', 'approvedBy']);

        return view('admin.payout-requests.show', ['payout' => $payoutRequest, 'events' => $events->forPayout($payoutRequest->id), 'logs' => $trail->latestFor($payoutRequest)]);
    }

    public function startReview(StartPayoutReviewRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $this->review->startReview($this->actor(), $payoutRequest);

        return $this->done(__('admin.payouts.reviewing'));
    }

    public function approve(ApprovePayoutRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $payout = $this->review->approve($this->actor(), $payoutRequest, $request->reason());

        return $this->done($payout->status->value === 'approved' ? __('admin.payouts.approved') : __('admin.payouts.first_approval'));
    }

    public function reject(RejectPayoutRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $this->review->reject($this->actor(), $payoutRequest, (string) $request->reason());

        return $this->done(__('admin.payouts.rejected'));
    }

    public function markProcessing(MarkPayoutProcessingRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $this->settlement->markProcessing($this->actor(), $payoutRequest);

        return $this->done(__('admin.payouts.processing'));
    }

    public function markPaid(MarkPayoutPaidRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $this->settlement->markPaid($this->actor(), $payoutRequest, (string) $request->validated('transfer_reference'), $request->file('evidence'), $request->reason());

        return $this->done(__('admin.payouts.paid'));
    }

    public function markFailed(MarkPayoutFailedRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $this->settlement->markFailed($this->actor(), $payoutRequest, (string) $request->reason());

        return $this->done(__('admin.payouts.failed'));
    }

    public function reverse(ReversePayoutRequest $request, PayoutRequest $payoutRequest): AdminFormResponse
    {
        $this->settlement->reverse($this->actor(), $payoutRequest, (string) $request->reason());

        return $this->done(__('admin.payouts.reversed'));
    }

    /**
     * The transfer receipt (private disk, never public; payouts.view).
     */
    public function evidence(PayoutRequest $payoutRequest, PrivateFileStorage $files): StreamedResponse
    {
        abort_unless($files->exists($payoutRequest->evidence_path), 404);

        return $files->response((string) $payoutRequest->evidence_path, 'application/octet-stream', true, 'payout-'.substr($payoutRequest->uuid, 0, 8).'-evidence');
    }

    private function done(string $message): AdminFormResponse
    {
        return $this->formResponse($message)->reloadTable(PayoutRequestsDataTable::TABLE_ID)->reload();
    }
}
