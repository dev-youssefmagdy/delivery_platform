<?php

namespace App\Http\Controllers\Admin\Payout;

use App\DataTables\Admin\Payout\BatchPayoutsDataTable;
use App\DataTables\Admin\Payout\PayoutBatchesDataTable;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payout\CreatePayoutBatchRequest;
use App\Models\PayoutBatch;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Payout\PayoutBatchService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payout batches (payouts.view; creating and exporting need payouts.review). A batch groups the approved payouts of one currency
 * for one transfer run; the transfer file is streamed (never stored), audited, and marks the batch exported.
 */
final class PayoutBatchController extends AdminController
{
    private const MAX_BATCH = 200;

    public function __construct(private readonly PayoutBatchService $batches) {}

    public function index(PayoutBatchesDataTable $table): mixed
    {
        return $table->render('admin.payout-batches.index');
    }

    public function show(BatchPayoutsDataTable $table, PayoutBatch $payoutBatch, AuditTrailService $trail): mixed
    {
        $payoutBatch->load('createdBy');

        return $table->render('admin.payout-batches.show', ['batch' => $payoutBatch, 'logs' => $trail->latestFor($payoutBatch)]);
    }

    public function store(CreatePayoutBatchRequest $request, PayoutRequestRepositoryInterface $payouts): AdminFormResponse
    {
        $ids = $payouts->approvedUnbatchedIds(self::MAX_BATCH);

        if ($ids === []) {
            throw BusinessRuleViolation::make('payout_batch_empty');
        }

        $batch = $this->batches->create($this->actor(), $ids, $request->reason());

        return $this->formResponse(__('admin.batches.created', ['reference' => $batch->reference]))->redirect(route('admin.payout-batches.show', $batch));
    }

    public function export(Request $request, PayoutBatch $payoutBatch): StreamedResponse
    {
        $rows = $this->batches->exportRows($this->actor(), $payoutBatch);

        return response()->streamDownload(static function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                // Formula-injection guard: a cell that starts like a formula is prefixed.
                fputcsv($out, array_map(static fn (string $cell): string => $cell !== '' && ! is_numeric($cell) && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$cell : $cell, $row), escape: '');
            }

            fclose($out);
        }, 'payout-batch-'.$payoutBatch->reference.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
