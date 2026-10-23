<?php

namespace App\Services\Growth;

use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Growth\CreditStatus;
use App\Models\LedgerAccount;
use App\Models\LoyaltyAward;
use App\Models\ReferralReward;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\LoyaltyRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use Carbon\CarbonInterface;

/**
 * How much of each promotional credit a customer still has. Credits sit in the ordinary wallet, so the rule that keeps real money
 * safe is: money leaving the wallet (debit lines, expiry entries excluded) is taken from promotional credit FIRST, oldest credit first.
 * Expiry therefore only ever reclaims credit that was not spent, never the customer's own top-ups (and never more than the balance).
 */
final class CreditPositionService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly LoyaltyRepositoryInterface $loyalty,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly LedgerAccountRepositoryInterface $accounts,
    ) {}

    /**
     * @return array{credits: list<array{source: string, id: int, amount_minor: int, expired_minor: int, unspent_minor: int, status: CreditStatus, granted_at: CarbonInterface, expires_at: CarbonInterface|null, currency: string, model: ReferralReward|LoyaltyAward}>, available_minor: int, currency: string|null, wallet: LedgerAccount|null}
     */
    public function forCustomer(int $userId): array
    {
        $credits = [];

        foreach ($this->referrals->rewardsOfUser($userId, 500) as $reward) {
            if ($reward->wallet_type === LedgerAccountType::CustomerWallet->value && $reward->status !== CreditStatus::Reversed) {
                $credits[] = ['source' => 'referral', 'id' => $reward->id, 'amount_minor' => $reward->amount_minor, 'expired_minor' => $reward->expired_minor, 'status' => $reward->status,
                    'granted_at' => $reward->granted_at, 'expires_at' => $reward->expires_at, 'currency' => $reward->currency, 'model' => $reward];
            }
        }

        foreach ($this->loyalty->awardsOfUser($userId) as $award) {
            if ($award->status !== CreditStatus::Reversed && $award->amount_minor > 0) {
                $credits[] = ['source' => 'loyalty', 'id' => $award->id, 'amount_minor' => $award->amount_minor, 'expired_minor' => $award->expired_minor, 'status' => $award->status,
                    'granted_at' => $award->granted_at, 'expires_at' => $award->expires_at, 'currency' => $award->currency, 'model' => $award];
            }
        }

        if ($credits === []) {
            return ['credits' => [], 'available_minor' => 0, 'currency' => null, 'wallet' => null];
        }

        usort($credits, static fn (array $a, array $b): int => [$a['granted_at']->getTimestamp(), $a['id']] <=> [$b['granted_at']->getTimestamp(), $b['id']]);
        $currency = $credits[0]['currency'];
        $wallet = $this->accounts->forOwner(LedgerAccountType::CustomerWallet, $userId)->first(static fn (LedgerAccount $account): bool => $account->currency === $currency);
        $spent = $wallet instanceof LedgerAccount ? $this->data->walletDebitedMinor($wallet->id, $credits[0]['granted_at'], [JournalEntryType::CreditExpiry->value, JournalEntryType::Reversal->value]) : 0;
        $remaining = $spent;
        $lines = [];
        $available = 0;

        foreach ($credits as $credit) {
            $capacity = max(0, $credit['amount_minor'] - $credit['expired_minor']);
            $share = min($capacity, $remaining);
            $remaining -= $share;
            $unspent = $credit['status'] === CreditStatus::Granted ? $capacity - $share : 0;
            $available += $unspent;
            $lines[] = $credit + ['unspent_minor' => $unspent];
        }

        // The wallet balance is the hard ceiling of what can still be promotional credit.
        $balance = $wallet instanceof LedgerAccount ? max(0, $wallet->balance_minor) : 0;

        return ['credits' => $lines, 'available_minor' => min($available, $balance), 'currency' => $currency, 'wallet' => $wallet];
    }
}
