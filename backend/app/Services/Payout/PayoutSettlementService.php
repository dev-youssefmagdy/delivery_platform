<?php

namespace App\Services\Payout;

use App\Enums\Common\AuditAction;
use App\Enums\Payout\PayoutBatchStatus;
use App\Enums\Payout\PayoutStatus;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\PayoutRequest;
use App\Repositories\Contracts\Payout\PayoutBatchRepositoryInterface;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Support\Files\InspectedUpload;
use App\Support\Files\PrivateFileStorage;
use App\Support\Files\UploadInspector;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Throwable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * After approval: processing, paid and failed (SRS §12.3), plus a paid transfer that came back (reversed).
 *
 * - paid converts the reservation: the payout clearing is cleared against gateway clearing (net) and platform revenue (fee).
 * - failed returns the reservation to the wallet (reversal of the reserve entry), so the provider can ask again.
 * - reversed (a paid transfer returned by the bank) reverses the paid entry and the reservation.
 * Each step re-reads the payout under FOR UPDATE and refuses any other starting state, so a repeated or racing call cannot pay
 * twice: the second one finds the payout already paid.
 */
final class PayoutSettlementService
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $payouts,
        private readonly PayoutBatchRepositoryInterface $batches,
        private readonly PayoutPostingService $postings,
        private readonly PayoutHistory $history,
        private readonly AuditLogger $audit,
        private readonly UploadInspector $inspector,
        private readonly PrivateFileStorage $files,
        private readonly DatabaseManager $db,
    ) {}

    public function markProcessing(Admin $actor, PayoutRequest $payout): PayoutRequest
    {
        return $this->db->transaction(function () use ($actor, $payout): PayoutRequest {
            $locked = $this->lock($payout);

            if ($locked->status !== PayoutStatus::Approved) {
                throw ConflictException::make('payout_state_conflict');
            }

            $this->payouts->update($locked, ['status' => PayoutStatus::Processing, 'processing_by_id' => $actor->id, 'processing_at' => CarbonImmutable::now()]);
            $this->history->record($locked, PayoutStatus::Approved, PayoutStatus::Processing, $actor);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: ['status' => PayoutStatus::Approved->value], after: ['status' => PayoutStatus::Processing->value]);

            return $locked;
        });
    }

    public function markPaid(Admin $actor, PayoutRequest $payout, string $transferReference, ?UploadedFile $evidence, ?string $note): PayoutRequest
    {
        // Inspected before any money moves: a bad file refuses the call without touching the ledger.
        $upload = $evidence instanceof UploadedFile
            ? $this->inspector->inspectDocument($evidence, ['application/pdf', 'image/jpeg', 'image/png'], (int) config('admin.payout_evidence_max_kb', 5120))
            : null;
        $stored = null;

        try {
            return $this->db->transaction(function () use ($actor, $payout, $transferReference, $upload, $note, &$stored): PayoutRequest {
                $locked = $this->lock($payout);

                if ($locked->status !== PayoutStatus::Processing) {
                    throw ConflictException::make('payout_state_conflict');
                }

                $stored = $upload instanceof InspectedUpload ? $this->files->putFinanceEvidence('payouts', $locked->uuid, $upload) : null;
                $locked->transfer_reference = $transferReference;
                $entry = $this->postings->pay($locked, $actor);
                $this->payouts->update($locked, [
                    'status' => PayoutStatus::Paid,
                    'transfer_reference' => $transferReference,
                    'evidence_path' => $stored,
                    'paid_entry_id' => $entry->id,
                    'paid_at' => CarbonImmutable::now(),
                    'closed_at' => CarbonImmutable::now(),
                ]);
                $this->history->record($locked, PayoutStatus::Processing, PayoutStatus::Paid, $actor, $note, ['transfer_reference' => $transferReference, 'evidence' => $stored !== null]);
                $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: ['status' => PayoutStatus::Processing->value], after: ['status' => PayoutStatus::Paid->value, 'transfer_reference' => $transferReference], reason: $note);
                $this->refreshBatch($locked);

                return $locked;
            });
        } catch (Throwable $exception) {
            // The transaction rolled back; do not leave the evidence file of a payment that did not happen.
            $this->files->delete($stored);

            throw $exception;
        }
    }

    public function markFailed(Admin $actor, PayoutRequest $payout, string $reason): PayoutRequest
    {
        return $this->db->transaction(function () use ($actor, $payout, $reason): PayoutRequest {
            $locked = $this->lock($payout);

            if ($locked->status !== PayoutStatus::Processing) {
                throw ConflictException::make('payout_state_conflict');
            }

            $release = $this->postings->release($locked, 'payout_failed', $actor);
            $this->payouts->update($locked, ['status' => PayoutStatus::Failed, 'failure_reason' => $reason, 'release_entry_id' => $release->id, 'closed_at' => CarbonImmutable::now()]);
            $this->history->record($locked, PayoutStatus::Processing, PayoutStatus::Failed, $actor, $reason);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: ['status' => PayoutStatus::Processing->value], after: ['status' => PayoutStatus::Failed->value], reason: $reason);
            $this->refreshBatch($locked);

            return $locked;
        });
    }

    public function reverse(Admin $actor, PayoutRequest $payout, string $reason): PayoutRequest
    {
        return $this->db->transaction(function () use ($actor, $payout, $reason): PayoutRequest {
            $locked = $this->lock($payout);

            if ($locked->status !== PayoutStatus::Paid) {
                throw ConflictException::make('payout_state_conflict');
            }

            $release = $this->postings->reversePaid($locked, $reason, $actor);
            $this->payouts->update($locked, ['status' => PayoutStatus::Reversed, 'failure_reason' => $reason, 'release_entry_id' => $release->id, 'closed_at' => CarbonImmutable::now()]);
            $this->history->record($locked, PayoutStatus::Paid, PayoutStatus::Reversed, $actor, $reason);
            $this->audit->record(AuditAction::Reversed, $locked, $actor, before: ['status' => PayoutStatus::Paid->value], after: ['status' => PayoutStatus::Reversed->value], reason: $reason);

            return $locked;
        });
    }

    /**
     * A batch is completed once none of its payouts is still being processed.
     */
    private function refreshBatch(PayoutRequest $payout): void
    {
        if ($payout->payout_batch_id === null) {
            return;
        }

        $batch = $this->batches->lockById($payout->payout_batch_id);

        if ($batch === null || $batch->status === PayoutBatchStatus::Completed) {
            return;
        }

        if ($this->payouts->forBatch($batch->id)->doesntContain(static fn (PayoutRequest $item): bool => $item->status === PayoutStatus::Processing)) {
            $this->batches->update($batch, ['status' => PayoutBatchStatus::Completed, 'completed_at' => CarbonImmutable::now()]);
        }
    }

    private function lock(PayoutRequest $payout): PayoutRequest
    {
        return $this->payouts->lockById($payout->id) ?? throw (new ModelNotFoundException)->setModel(PayoutRequest::class);
    }
}
