<?php

namespace App\Services\Growth;

use App\Enums\Growth\CreditStatus;
use App\Models\LoyaltyAward;
use App\Models\ReferralReward;
use App\Repositories\Contracts\Growth\LoyaltyRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Throwable;

/**
 * Expires promotional credit (SRS 11.3): after `expires_at` the unspent part (see CreditPositionService) is taken back out of the wallet by
 * one compensating ledger entry keyed `credit-expiry:<source>:<id>`, and the credit becomes `expired`. Spent credit is simply closed.
 * A customer whose wallet cannot cover the entry right now is skipped and retried by the next run.
 */
final class CreditExpiryService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly LoyaltyRepositoryInterface $loyalty,
        private readonly CreditPositionService $positions,
        private readonly GrowthLedgerPoster $poster,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @return int credits closed in this run
     */
    public function run(CarbonInterface $now, int $limit): int
    {
        $users = array_values(array_unique(array_merge($this->referrals->usersWithDueRewards($now, $limit), $this->loyalty->usersWithDueAwards($now, $limit))));
        $closed = 0;

        foreach ($users as $userId) {
            try {
                $closed += $this->expireFor($userId, $now);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $closed;
    }

    public function expireFor(int $userId, CarbonInterface $now): int
    {
        return $this->db->transaction(function () use ($userId, $now): int {
            $position = $this->positions->forCustomer($userId);
            $wallet = $position['wallet'];
            $room = $position['available_minor'];
            $closed = 0;

            foreach ($position['credits'] as $credit) {
                if ($credit['status'] !== CreditStatus::Granted || $credit['expires_at'] === null || $credit['expires_at']->gt($now) || $wallet === null) {
                    continue;
                }

                $reclaim = min($credit['unspent_minor'], $room);
                $model = $credit['model'];
                $attributes = ['status' => CreditStatus::Expired];

                if ($reclaim > 0) {
                    $entry = $this->poster->takeBack($wallet, $reclaim, 'credit-expiry:'.$credit['source'].':'.$credit['id'], $credit['source'] === 'referral' ? 'referral_reward' : 'loyalty_award', $credit['id'], ['credit' => $credit['id'], 'source' => $credit['source']]);
                    $attributes += ['expired_minor' => $credit['expired_minor'] + $reclaim, 'expiry_entry_id' => $entry->id];
                    $room -= $reclaim;
                }

                $model instanceof ReferralReward ? $this->referrals->updateReward($model, $attributes) : $this->loyalty->updateAward($model instanceof LoyaltyAward ? $model : throw new \LogicException, $attributes);
                $closed++;
            }

            return $closed;
        });
    }
}
