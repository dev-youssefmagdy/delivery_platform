<?php

namespace App\Services\Growth;

use App\Enums\Common\AuditAction;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\ReferralRelationship;
use App\Models\User;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Provider\ProviderOnboardingService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Applying a referral code (SRS 11.3). A person can be referred once per audience, only while still new (no completed job), never by
 * themselves, only into an active campaign that still has room for the referrer (per-referrer cap). Weak fraud signals do not refuse
 * the code: they put the relationship under review so the reward waits for a person (never an automatic punishment).
 */
final class ReferralService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly ReferralFraudChecker $fraud,
        private readonly ProviderOnboardingService $providers,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Idempotent: applying the same code again returns the same relationship.
     *
     * @throws BusinessRuleViolation `referral_code_invalid`, `referral_self`, `referral_not_new`, `referral_campaign_unavailable`, `referral_cap_reached`
     * @throws ConflictException `referral_already_applied`
     */
    public function apply(User $referee, ReferralAudience $audience, string $rawCode): ReferralRelationship
    {
        $code = $this->referrals->findCode(ReferralCodeService::normalize($rawCode));

        if ($code === null || $code->audience !== $audience) {
            throw BusinessRuleViolation::make('referral_code_invalid', 'code');
        }

        if ($code->user_id === $referee->id) {
            throw BusinessRuleViolation::make('referral_self', 'code');
        }

        $existing = $this->referrals->relationshipOfReferee($referee->id, $audience);

        if ($existing instanceof ReferralRelationship) {
            return $existing->referral_code_id === $code->id ? $existing : throw ConflictException::make('referral_already_applied');
        }

        if (! $this->isNew($referee, $audience)) {
            throw BusinessRuleViolation::make('referral_not_new', 'code');
        }

        $campaign = $this->referrals->activeCampaign($audience, now()) ?? throw BusinessRuleViolation::make('referral_campaign_unavailable', 'code');
        $referrer = $this->data->user($code->user_id) ?? throw BusinessRuleViolation::make('referral_code_invalid', 'code');

        if ($this->referrals->relationshipsOfReferrerInCampaign($referrer->id, $campaign->id) >= $campaign->per_referrer_cap) {
            throw BusinessRuleViolation::make('referral_cap_reached', 'code');
        }

        $flags = $this->fraud->check($referrer, $referee, $audience);

        try {
            return $this->db->transaction(function () use ($campaign, $code, $audience, $referrer, $referee, $flags): ReferralRelationship {
                $relationship = $this->referrals->createRelationship([
                    'campaign_id' => $campaign->id,
                    'referral_code_id' => $code->id,
                    'audience' => $audience,
                    'referrer_user_id' => $referrer->id,
                    'referee_user_id' => $referee->id,
                    'status' => $flags === [] ? RelationshipStatus::Pending : RelationshipStatus::UnderReview,
                    'fraud_flags' => $flags === [] ? null : $flags,
                ]);
                $this->audit->record(AuditAction::Created, $relationship, null, after: ['status' => $relationship->status->value, 'flags' => $relationship->flagKeys()], meta: ['campaign' => $campaign->uuid]);

                return $relationship;
            });
        } catch (UniqueConstraintViolationException) {
            $raced = $this->referrals->relationshipOfReferee($referee->id, $audience);

            return $raced instanceof ReferralRelationship && $raced->referral_code_id === $code->id ? $raced : throw ConflictException::make('referral_already_applied');
        }
    }

    private function isNew(User $referee, ReferralAudience $audience): bool
    {
        if ($audience === ReferralAudience::Customer) {
            return $this->data->completedBookingsOfCustomer($referee->id) === 0;
        }

        $profile = $this->providers->profileOf($referee);

        return $profile === null || $profile->completed_jobs === 0;
    }
}
