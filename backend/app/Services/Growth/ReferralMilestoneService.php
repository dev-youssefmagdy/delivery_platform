<?php

namespace App\Services\Growth;

use App\Enums\Common\AuditAction;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Models\Booking;
use App\Models\ReferralCampaign;
use App\Models\ReferralRelationship;
use App\Models\User;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;

/**
 * Counts a completed booking towards the referee's milestone and pays when it is reached. Qualifying jobs follow the campaign:
 * minimum order, service categories, cities. At the milestone the fraud checks run once more (velocity is not repeated); a flag moves
 * the relationship to review and the reward waits. A person who already approved the relationship is not flagged again.
 */
final class ReferralMilestoneService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly ReferralFraudChecker $fraud,
        private readonly ReferralRewardService $rewards,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function onCompleted(Booking $booking, bool $customerSide, bool $providerSide): void
    {
        $customer = $customerSide ? $this->data->user($booking->customer_id) : null;

        if ($customer instanceof User) {
            $this->count($customer, ReferralAudience::Customer, $booking);
        }

        if ($providerSide && $booking->provider_id !== null) {
            $providerUser = $this->data->userOfProvider($booking->provider_id);

            if ($providerUser instanceof User) {
                $this->count($providerUser, ReferralAudience::Provider, $booking);
            }
        }
    }

    /**
     * Retries qualified relationships that waited for budget or an active campaign.
     */
    public function settleWaiting(int $limit): int
    {
        $granted = 0;

        foreach ($this->referrals->waitingForReward($limit) as $relationship) {
            $granted += $this->rewards->grant($relationship) ? 1 : 0;
        }

        return $granted;
    }

    private function count(User $referee, ReferralAudience $audience, Booking $booking): void
    {
        $relationship = $this->referrals->relationshipOfReferee($referee->id, $audience);

        if (! $relationship instanceof ReferralRelationship || ! in_array($relationship->status, [RelationshipStatus::Pending, RelationshipStatus::UnderReview], true)) {
            return;
        }

        $this->db->transaction(function () use ($relationship, $referee, $audience, $booking): void {
            $locked = $this->referrals->lockRelationship($relationship->id);
            $campaign = $locked === null ? null : $this->referrals->findCampaign($locked->campaign_id);

            if ($locked === null || $campaign === null || ! in_array($locked->status, [RelationshipStatus::Pending, RelationshipStatus::UnderReview], true) || ! $this->qualifies($campaign, $booking)) {
                return;
            }

            $jobs = $locked->jobs_completed + 1;
            $this->referrals->updateRelationship($locked, ['jobs_completed' => $jobs]);

            if ($jobs < $campaign->milestone_jobs || $locked->qualified_at !== null) {
                return;
            }

            $this->referrals->updateRelationship($locked, ['qualified_at' => now()]);
            $approved = $locked->decided_at !== null;
            $referrer = $this->data->user($locked->referrer_user_id);
            $flags = $approved || $referrer === null ? [] : $this->fraud->check($referrer, $referee, $audience, countVelocity: false);

            if ($flags !== []) {
                $merged = array_values(array_unique(array_merge($locked->flagKeys(), array_map(static fn (array $flag): string => $flag['flag'], $flags))));
                $known = $locked->fraud_flags ?? [];

                foreach ($flags as $flag) {
                    if (! in_array($flag['flag'], $locked->flagKeys(), true)) {
                        $known[] = $flag;
                    }
                }

                $this->referrals->updateRelationship($locked, ['status' => RelationshipStatus::UnderReview, 'fraud_flags' => $known]);
                $this->audit->record(AuditAction::StatusChanged, $locked, null, after: ['status' => RelationshipStatus::UnderReview->value, 'flags' => $merged]);

                return;
            }
        });

        $fresh = $this->referrals->relationshipOfReferee($referee->id, $audience);

        if ($fresh instanceof ReferralRelationship && $fresh->status === RelationshipStatus::Pending && $fresh->qualified_at !== null) {
            $this->rewards->grant($fresh);
        }
    }

    private function qualifies(ReferralCampaign $campaign, Booking $booking): bool
    {
        $fare = $booking->final_fare_minor ?? $booking->agreed_fare_minor ?? 0;

        return ($campaign->min_order_minor === null || $fare >= $campaign->min_order_minor)
            && ($campaign->service_category_ids === null || $campaign->service_category_ids === [] || in_array($booking->service_category_id, $campaign->service_category_ids, true))
            && ($campaign->city_ids === null || $campaign->city_ids === [] || in_array($booking->city_id, $campaign->city_ids, true));
    }
}
