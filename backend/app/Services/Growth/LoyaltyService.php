<?php

namespace App\Services\Growth;

use App\Enums\Finance\JournalEntryType;
use App\Enums\Growth\CreditStatus;
use App\Models\LoyaltyLevel;
use App\Models\LoyaltyMember;
use App\Models\User;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\LoyaltyRepositoryInterface;
use App\Services\Finance\LedgerAccountResolver;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;

/**
 * Loyalty by completed-job count (SRS 11.3). Each completed booking adds one job; the level is the highest active level whose
 * threshold the count reached. Reaching a level for the first time grants its one-time credit (ledger entry with an expiry), once per
 * user and level (unique row + idempotency key `loyalty:<user>:<level>`).
 */
final class LoyaltyService
{
    public function __construct(
        private readonly LoyaltyRepositoryInterface $loyalty,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly GrowthLedgerPoster $poster,
        private readonly LedgerAccountResolver $accounts,
        private readonly DatabaseManager $db,
    ) {}

    public function onCompleted(int $customerId): void
    {
        $user = $this->data->user($customerId);

        if (! $user instanceof User) {
            return;
        }

        $this->db->transaction(function () use ($user): void {
            $member = $this->loyalty->lockOrCreateMember($user->id);
            $jobs = $member->completed_jobs + 1;
            $reached = null;

            foreach ($this->loyalty->levels(activeOnly: true) as $level) {
                if ($level->min_completed_jobs <= $jobs) {
                    $reached = $level;
                }
            }

            $attributes = ['completed_jobs' => $jobs];

            if ($reached instanceof LoyaltyLevel && $reached->id !== $member->level_id) {
                $attributes += ['level_id' => $reached->id, 'level_reached_at' => now()];
            }

            $this->loyalty->updateMember($member, $attributes);

            // Every level crossed (a customer can cross several at once after a level is added) gets its credit once.
            foreach ($this->loyalty->levels(activeOnly: true) as $level) {
                if ($level->min_completed_jobs <= $jobs && $level->reward_minor > 0) {
                    $this->award($user, $level);
                }
            }
        });
    }

    /**
     * @return array{jobs: int, level: LoyaltyLevel|null, next: LoyaltyLevel|null, jobs_to_next: int|null, levels: \Illuminate\Support\Collection<int, LoyaltyLevel>}
     */
    public function statusFor(User $user): array
    {
        $member = $this->loyalty->memberOf($user->id);
        $jobs = $member instanceof LoyaltyMember ? $member->completed_jobs : 0;
        $levels = $this->loyalty->levels(activeOnly: true);
        $current = $levels->filter(static fn (LoyaltyLevel $level): bool => $level->min_completed_jobs <= $jobs)->last();
        $next = $levels->first(static fn (LoyaltyLevel $level): bool => $level->min_completed_jobs > $jobs);

        return [
            'jobs' => $jobs,
            'level' => $current,
            'next' => $next,
            'jobs_to_next' => $next instanceof LoyaltyLevel ? $next->min_completed_jobs - $jobs : null,
            'levels' => $levels,
        ];
    }

    private function award(User $user, LoyaltyLevel $level): void
    {
        if ($this->loyalty->awardOf($user->id, $level->id) !== null) {
            return;
        }

        $key = 'loyalty:'.$user->id.':'.$level->id;
        $award = $this->loyalty->createAward([
            'user_id' => $user->id, 'level_id' => $level->id, 'amount_minor' => $level->reward_minor, 'currency' => $level->currency, 'status' => CreditStatus::Granted,
            'granted_at' => now(), 'expires_at' => Carbon::now()->addDays($level->credit_valid_days), 'idempotency_key' => $key,
        ]);
        $entry = $this->poster->grant($this->accounts->customerWallet($user, $level->currency), $level->reward_minor, JournalEntryType::LoyaltyReward, $key, 'loyalty_award', $award->id, ['level' => $level->id]);
        $this->loyalty->updateAward($award, ['journal_entry_id' => $entry->id]);
    }
}
