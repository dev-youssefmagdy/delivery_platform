<?php

namespace App\Services\Payout;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Payout\PayoutStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenActionException;
use App\Models\Admin;
use App\Models\PayoutRequest;
use App\Models\ProviderPayoutMethod;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Admin decisions before the money moves (SRS §12.3): review, approve (dual above the threshold) and reject.
 *
 * Dual approval: below the `payout_dual_approval_threshold_minor` setting one `payouts.approve` admin approves; at or above it
 * TWO DIFFERENT admins with `payouts.approve` are needed - the first approval is recorded and the request stays under review,
 * the second (never the same admin) approves it. Every step runs under the payout's row lock and is audited with its note.
 */
final class PayoutReviewService
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $payouts,
        private readonly PayoutPostingService $postings,
        private readonly PayoutHistory $history,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function startReview(Admin $actor, PayoutRequest $payout): PayoutRequest
    {
        return $this->db->transaction(function () use ($actor, $payout): PayoutRequest {
            $locked = $this->lock($payout);

            if ($locked->status !== PayoutStatus::Requested) {
                throw ConflictException::make('payout_state_conflict');
            }

            $this->payouts->update($locked, ['status' => PayoutStatus::UnderReview, 'reviewed_by_id' => $actor->id, 'reviewed_at' => CarbonImmutable::now()]);
            $this->history->record($locked, PayoutStatus::Requested, PayoutStatus::UnderReview, $actor);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, before: ['status' => PayoutStatus::Requested->value], after: ['status' => PayoutStatus::UnderReview->value]);

            return $locked;
        });
    }

    public function approve(Admin $actor, PayoutRequest $payout, ?string $note): PayoutRequest
    {
        return $this->db->transaction(function () use ($actor, $payout, $note): PayoutRequest {
            $locked = $this->lock($payout);

            if (! in_array($locked->status, PayoutStatus::awaitingDecision(), true)) {
                throw ConflictException::make('payout_state_conflict');
            }

            if (! $actor->can(AdminPermission::PayoutsApprove->value)) {
                throw ForbiddenActionException::make('payout_approval_forbidden');
            }

            $method = $locked->method;

            if (! $method instanceof ProviderPayoutMethod || $method->trashed() || ! $method->isVerified()) {
                throw BusinessRuleViolation::make('payout_method_not_verified');
            }

            $from = $locked->status;
            $now = CarbonImmutable::now();
            $reviewer = ['reviewed_by_id' => $locked->reviewed_by_id ?? $actor->id, 'reviewed_at' => $locked->reviewed_at ?? $now];

            if ($locked->requires_dual_approval && $locked->first_approved_by_id === null) {
                $this->payouts->update($locked, $reviewer + ['status' => PayoutStatus::UnderReview, 'first_approved_by_id' => $actor->id, 'first_approved_at' => $now, 'decision_note' => $note]);
                $this->history->record($locked, $from, PayoutStatus::UnderReview, $actor, $note, ['step' => 'first_approval']);
                $this->audit->record(AuditAction::Approved, $locked, $actor, after: ['status' => PayoutStatus::UnderReview->value, 'step' => 'first_approval'], reason: $note);

                return $locked;
            }

            if ($locked->requires_dual_approval && $locked->first_approved_by_id === $actor->id) {
                throw ForbiddenActionException::make('payout_same_approver');
            }

            $this->payouts->update($locked, $reviewer + ['status' => PayoutStatus::Approved, 'approved_by_id' => $actor->id, 'approved_at' => $now, 'decision_note' => $note ?? $locked->decision_note]);
            $this->history->record($locked, $from, PayoutStatus::Approved, $actor, $note, ['step' => $locked->requires_dual_approval ? 'second_approval' : 'approval']);
            $this->audit->record(AuditAction::Approved, $locked, $actor, after: ['status' => PayoutStatus::Approved->value, 'dual_approval' => $locked->requires_dual_approval], reason: $note);

            return $locked;
        });
    }

    public function reject(Admin $actor, PayoutRequest $payout, string $reason): PayoutRequest
    {
        return $this->db->transaction(function () use ($actor, $payout, $reason): PayoutRequest {
            $locked = $this->lock($payout);

            if (! in_array($locked->status, [...PayoutStatus::awaitingDecision(), PayoutStatus::Approved], true)) {
                throw ConflictException::make('payout_state_conflict');
            }

            if ($locked->payout_batch_id !== null) {
                throw ConflictException::make('payout_in_batch');
            }

            $release = $this->postings->release($locked, 'payout_rejected', $actor);
            $from = $locked->status;
            $this->payouts->update($locked, ['status' => PayoutStatus::Rejected, 'rejection_reason' => $reason, 'release_entry_id' => $release->id, 'closed_at' => CarbonImmutable::now()]);
            $this->history->record($locked, $from, PayoutStatus::Rejected, $actor, $reason);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, before: ['status' => $from->value], after: ['status' => PayoutStatus::Rejected->value], reason: $reason);

            return $locked;
        });
    }

    private function lock(PayoutRequest $payout): PayoutRequest
    {
        return $this->payouts->lockById($payout->id) ?? throw (new ModelNotFoundException)->setModel(PayoutRequest::class);
    }
}
