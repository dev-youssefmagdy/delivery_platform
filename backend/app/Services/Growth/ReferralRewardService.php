<?php

namespace App\Services\Growth;

use App\Enums\Common\AuditAction;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\CreditStatus;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Enums\Growth\RewardRecipient;
use App\Models\LedgerAccount;
use App\Models\ReferralCampaign;
use App\Models\ReferralRelationship;
use App\Models\User;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Finance\LedgerAccountResolver;
use App\Services\Provider\ProviderOnboardingService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;

/**
 * Pays the two rewards of a qualified referral through the ledger. Everything happens in one transaction with the campaign row locked:
 * the campaign must be active, the referrer must be under the per-referrer cap and the budget must cover both rewards, otherwise the
 * relationship waits (status stays pending, `qualified_at` set) and `growth:settle-referrals` retries. A reward row is keyed
 * `referral-reward:<relationship uuid>:<recipient>`, so a second run pays nothing twice.
 */
final class ReferralRewardService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly GrowthLedgerPoster $poster,
        private readonly LedgerAccountResolver $accounts,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly ProviderOnboardingService $providers,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return bool true when the rewards are (now) granted
     */
    public function grant(ReferralRelationship $relationship): bool
    {
        return $this->db->transaction(function () use ($relationship): bool {
            $locked = $this->referrals->lockRelationship($relationship->id);

            if ($locked === null || $locked->status === RelationshipStatus::Rewarded) {
                return $locked?->status === RelationshipStatus::Rewarded;
            }

            if ($locked->status !== RelationshipStatus::Pending) {
                return false;
            }

            $campaign = $this->referrals->lockCampaign($locked->campaign_id);

            if ($campaign === null || $campaign->status !== CampaignStatus::Active) {
                return false;
            }

            $total = $campaign->referrer_reward_minor + $campaign->referee_reward_minor;

            if ($total > $campaign->remainingBudgetMinor()
                || $this->referrals->referrerRewardCount($locked->referrer_user_id, $campaign->id) >= $campaign->per_referrer_cap) {
                return false;
            }

            $referrer = $this->data->user($locked->referrer_user_id);
            $referee = $this->data->user($locked->referee_user_id);

            if ($referrer === null || $referee === null) {
                return false;
            }

            $posted = 0;

            foreach ([[RewardRecipient::Referrer, $referrer, $campaign->referrer_reward_minor], [RewardRecipient::Referee, $referee, $campaign->referee_reward_minor]] as [$recipient, $user, $amount]) {
                $posted += $this->payOne($locked, $campaign, $recipient, $user, $amount);
            }

            $this->referrals->updateCampaign($campaign, ['spent_minor' => $campaign->spent_minor + $posted]);
            $this->referrals->updateRelationship($locked, ['status' => RelationshipStatus::Rewarded, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::StatusChanged, $locked, null, after: ['status' => RelationshipStatus::Rewarded->value, 'spent_minor' => $posted], meta: ['campaign' => $campaign->uuid]);

            return true;
        });
    }

    private function payOne(ReferralRelationship $relationship, ReferralCampaign $campaign, RewardRecipient $recipient, User $user, int $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $key = 'referral-reward:'.$relationship->uuid.':'.$recipient->value;

        if ($this->referrals->findRewardByKey($key) !== null) {
            return 0;
        }

        $wallet = $this->wallet($user, $relationship->audience, $campaign->currency);

        if (! $wallet instanceof LedgerAccount) {
            return 0;
        }

        // Customer credit expires; a provider bonus is cash-like and does not.
        $expiresAt = $relationship->audience === ReferralAudience::Customer ? Carbon::now()->addDays($campaign->credit_valid_days) : null;
        $reward = $this->referrals->createReward([
            'relationship_id' => $relationship->id, 'campaign_id' => $campaign->id, 'user_id' => $user->id, 'recipient' => $recipient,
            'wallet_type' => $wallet->type->value, 'amount_minor' => $amount, 'currency' => $campaign->currency, 'status' => CreditStatus::Granted,
            'granted_at' => now(), 'expires_at' => $expiresAt, 'idempotency_key' => $key,
        ]);
        $entry = $this->poster->grant($wallet, $amount, JournalEntryType::ReferralReward, $key, 'referral_reward', $reward->id, ['relationship' => $relationship->uuid, 'recipient' => $recipient->value]);
        $this->referrals->updateReward($reward, ['journal_entry_id' => $entry->id]);

        return $amount;
    }

    private function wallet(User $user, ReferralAudience $audience, string $currency): ?LedgerAccount
    {
        if ($audience === ReferralAudience::Customer) {
            return $this->accounts->customerWallet($user, $currency);
        }

        $profile = $this->providers->profileOf($user);

        return $profile === null ? null : $this->accounts->providerWallet($profile, $currency);
    }
}
