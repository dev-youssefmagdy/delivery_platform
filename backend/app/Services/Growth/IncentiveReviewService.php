<?php

namespace App\Services\Growth;

use App\Enums\Common\AuditAction;
use App\Enums\Growth\IncentiveProgressStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\IncentiveProgress;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Manual decision on a held quest progress: release (the bonus is paid for the jobs already counted) or reject (no payout, reason
 * required). No provider is suspended or restricted by this module.
 */
final class IncentiveReviewService
{
    public function __construct(
        private readonly IncentiveRepositoryInterface $incentives,
        private readonly IncentiveService $engine,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ConflictException `incentive_not_held`
     */
    public function release(IncentiveProgress $progress, Admin $actor, ?string $note): IncentiveProgress
    {
        return $this->db->transaction(function () use ($progress, $actor, $note): IncentiveProgress {
            $campaign = $this->incentives->lockCampaign($progress->campaign_id) ?? throw (new ModelNotFoundException)->setModel(IncentiveProgress::class);
            $locked = $this->incentives->lockProgress($progress->id) ?? throw (new ModelNotFoundException)->setModel(IncentiveProgress::class);

            if ($locked->status !== IncentiveProgressStatus::Held) {
                throw ConflictException::make('incentive_not_held');
            }

            $this->incentives->updateProgress($locked, ['status' => IncentiveProgressStatus::InProgress, 'decided_by_admin_id' => $actor->id, 'decision_reason' => $note]);
            $paid = $this->engine->settleReleased($campaign, $locked);
            $this->audit->record(AuditAction::Approved, $locked, $actor, before: ['status' => IncentiveProgressStatus::Held->value], after: ['status' => $locked->status->value, 'awards_paid' => $paid], reason: $note);

            return $locked;
        });
    }

    /**
     * @throws ConflictException `incentive_not_held`
     * @throws BusinessRuleViolation `incentive_reason_required`
     */
    public function reject(IncentiveProgress $progress, Admin $actor, string $reason): IncentiveProgress
    {
        return $this->db->transaction(function () use ($progress, $actor, $reason): IncentiveProgress {
            $locked = $this->incentives->lockProgress($progress->id) ?? throw (new ModelNotFoundException)->setModel(IncentiveProgress::class);

            if (trim($reason) === '') {
                throw BusinessRuleViolation::make('incentive_reason_required', 'reason');
            }

            if ($locked->status !== IncentiveProgressStatus::Held) {
                throw ConflictException::make('incentive_not_held');
            }

            $this->incentives->updateProgress($locked, ['status' => IncentiveProgressStatus::Rejected, 'decided_by_admin_id' => $actor->id, 'decision_reason' => $reason]);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, before: ['status' => IncentiveProgressStatus::Held->value], after: ['status' => IncentiveProgressStatus::Rejected->value], reason: $reason);

            return $locked;
        });
    }
}
