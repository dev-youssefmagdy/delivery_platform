<?php

namespace App\Services\Growth;

use App\Enums\Common\AuditAction;
use App\Enums\Growth\RelationshipStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\ReferralRelationship;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The manual review of a flagged referral (the risk queue entry point): a person clears it (rewards are paid when the milestone is
 * reached) or rejects it with a reason. Nothing else is touched: no account is suspended or restricted by this module.
 */
final class ReferralReviewService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly ReferralRewardService $rewards,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws ConflictException `referral_not_under_review`
     */
    public function approve(ReferralRelationship $relationship, Admin $actor, ?string $note): ReferralRelationship
    {
        $locked = $this->db->transaction(function () use ($relationship, $actor, $note): ReferralRelationship {
            $locked = $this->referrals->lockRelationship($relationship->id) ?? throw (new ModelNotFoundException)->setModel(ReferralRelationship::class);

            if ($locked->status !== RelationshipStatus::UnderReview) {
                throw ConflictException::make('referral_not_under_review');
            }

            $campaign = $this->referrals->findCampaign($locked->campaign_id);
            $reached = $campaign !== null && $locked->jobs_completed >= $campaign->milestone_jobs;
            $this->referrals->updateRelationship($locked, [
                'status' => RelationshipStatus::Pending, 'decided_at' => now(), 'decided_by_admin_id' => $actor->id, 'decision_reason' => $note,
                'qualified_at' => $locked->qualified_at ?? ($reached ? now() : null), 'lock_version' => $locked->lock_version + 1,
            ]);
            $this->audit->record(AuditAction::Approved, $locked, $actor, before: ['status' => RelationshipStatus::UnderReview->value], after: ['status' => RelationshipStatus::Pending->value, 'flags' => $locked->flagKeys()], reason: $note);

            return $locked;
        });

        if ($locked->qualified_at !== null) {
            $this->rewards->grant($locked);
        }

        return $locked->refresh();
    }

    /**
     * @throws ConflictException `referral_not_under_review`
     * @throws BusinessRuleViolation `referral_reason_required`
     */
    public function reject(ReferralRelationship $relationship, Admin $actor, string $reason): ReferralRelationship
    {
        return $this->db->transaction(function () use ($relationship, $actor, $reason): ReferralRelationship {
            $locked = $this->referrals->lockRelationship($relationship->id) ?? throw (new ModelNotFoundException)->setModel(ReferralRelationship::class);

            if (trim($reason) === '') {
                throw BusinessRuleViolation::make('referral_reason_required', 'reason');
            }

            if ($locked->status !== RelationshipStatus::UnderReview) {
                throw ConflictException::make('referral_not_under_review');
            }

            $this->referrals->updateRelationship($locked, [
                'status' => RelationshipStatus::Rejected, 'decided_at' => now(), 'decided_by_admin_id' => $actor->id, 'decision_reason' => $reason, 'lock_version' => $locked->lock_version + 1,
            ]);
            $this->audit->record(AuditAction::Rejected, $locked, $actor, before: ['status' => RelationshipStatus::UnderReview->value], after: ['status' => RelationshipStatus::Rejected->value, 'flags' => $locked->flagKeys()], reason: $reason);

            return $locked;
        });
    }
}
