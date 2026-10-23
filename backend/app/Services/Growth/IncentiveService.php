<?php

namespace App\Services\Growth;

use App\Enums\Common\AuditAction;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\FraudFlag;
use App\Enums\Growth\IncentiveProgressStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Risk\RiskSubjectType;
use App\Models\Booking;
use App\Models\IncentiveCampaign;
use App\Models\IncentiveProgress;
use App\Models\ProviderProfile;
use App\Repositories\Contracts\Growth\GrowthDataRepositoryInterface;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Services\Audit\AuditLogger;
use App\Services\Finance\LedgerAccountResolver;
use Illuminate\Database\DatabaseManager;

/**
 * The provider quest engine (SRS 7.6). A completed booking counts for every open campaign the provider is eligible for (approved,
 * city, service category, minimum order, minimum rating). Fraud rules: a job whose customer shares a device with the provider (or is the
 * provider's own account) does not count and holds the progress for review; more than `max_jobs_per_customer` jobs for the same customer
 * do not count; a provider with an open risk signal (config) has the payout held for a person. A bonus is posted only when the target
 * is reached, the award cap is not exhausted and the budget covers it (otherwise the progress is `exhausted` and retried when the
 * budget is raised). The bonus is one ledger entry keyed `incentive:<progress uuid>:<sequence>`.
 */
final class IncentiveService
{
    public function __construct(
        private readonly IncentiveRepositoryInterface $incentives,
        private readonly GrowthDataRepositoryInterface $data,
        private readonly GrowthLedgerPoster $poster,
        private readonly LedgerAccountResolver $accounts,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function onCompleted(Booking $booking): void
    {
        if ($booking->provider_id === null) {
            return;
        }

        $profile = $this->data->providerProfile($booking->provider_id);

        if (! $profile instanceof ProviderProfile || $profile->status !== ProviderStatus::Approved) {
            return;
        }

        foreach ($this->incentives->openCampaigns(now()) as $campaign) {
            if ($this->eligible($campaign, $profile, $booking)) {
                $this->count($campaign, $profile, $booking);
            }
        }
    }

    /**
     * Retries progress that waited for budget. Returns how many bonuses were paid.
     */
    public function settleExhausted(int $limit): int
    {
        $paid = 0;

        foreach ($this->incentives->exhausted($limit) as $progress) {
            $paid += $this->db->transaction(function () use ($progress): int {
                $locked = $this->incentives->lockProgress($progress->id);
                $campaign = $locked === null ? null : $this->incentives->lockCampaign($locked->campaign_id);

                if ($locked === null || $campaign === null || $locked->status !== IncentiveProgressStatus::Exhausted || $campaign->status !== CampaignStatus::Active) {
                    return 0;
                }

                $this->incentives->updateProgress($locked, ['status' => IncentiveProgressStatus::InProgress]);

                return $this->settle($campaign, $locked, reviewed: false);
            });
        }

        return $paid;
    }

    /**
     * Pays what is due for a progress row that a person has just released. Call inside a transaction with both rows locked.
     */
    public function settleReleased(IncentiveCampaign $campaign, IncentiveProgress $progress): int
    {
        return $this->settle($campaign, $progress, reviewed: true);
    }

    private function eligible(IncentiveCampaign $campaign, ProviderProfile $profile, Booking $booking): bool
    {
        $fare = $booking->final_fare_minor ?? $booking->agreed_fare_minor ?? 0;

        return ($campaign->city_ids === null || $campaign->city_ids === [] || in_array($booking->city_id, $campaign->city_ids, true))
            && ($campaign->service_category_ids === null || $campaign->service_category_ids === [] || in_array($booking->service_category_id, $campaign->service_category_ids, true))
            && ($campaign->min_order_minor === null || $fare >= $campaign->min_order_minor)
            && ($campaign->min_rating === null || ($profile->rating_count > 0 && (float) $profile->rating_avg >= (float) $campaign->min_rating));
    }

    private function count(IncentiveCampaign $open, ProviderProfile $profile, Booking $booking): void
    {
        $this->db->transaction(function () use ($open, $profile, $booking): void {
            $campaign = $this->incentives->lockCampaign($open->id);
            $progress = $this->incentives->lockOrCreateProgress($open->id, $profile->id);

            if ($campaign === null || $progress->status === IncentiveProgressStatus::Rejected) {
                return;
            }

            $flags = $progress->flags ?? [];

            if ($this->sharesDevice($profile, $booking->customer_id)) {
                $this->flag($progress, $flags, FraudFlag::SameDevice, ['customer_shares_device' => true]);
                $this->incentives->updateProgress($progress, ['status' => IncentiveProgressStatus::Held, 'flags' => $flags]);
                $this->audit->record(AuditAction::StatusChanged, $progress, null, after: ['status' => IncentiveProgressStatus::Held->value, 'flag' => FraudFlag::SameDevice->value], meta: ['campaign' => $campaign->uuid]);

                return;
            }

            if ($this->incentives->incrementCustomerJobs($progress->id, $booking->customer_id) > $campaign->max_jobs_per_customer) {
                $this->flag($progress, $flags, FraudFlag::CustomerRepeat, ['max_jobs_per_customer' => $campaign->max_jobs_per_customer]);
                $this->incentives->updateProgress($progress, ['flags' => $flags]);

                return;
            }

            $this->incentives->updateProgress($progress, ['completed_jobs' => $progress->completed_jobs + 1, 'last_counted_at' => now(), 'flags' => $flags === [] ? null : $flags]);
            $this->settle($campaign, $progress, reviewed: false);
        });
    }

    /**
     * Pays every bonus the counted jobs have earned. Both rows are locked by the caller.
     */
    private function settle(IncentiveCampaign $campaign, IncentiveProgress $progress, bool $reviewed): int
    {
        $paid = 0;

        while ($progress->awards_count < $campaign->max_awards_per_provider && $progress->completed_jobs >= $campaign->target_jobs * ($progress->awards_count + 1)) {
            if ($progress->status === IncentiveProgressStatus::Held && ! $reviewed) {
                break;
            }

            if (! $reviewed && $this->data->hasOpenRiskSignal(RiskSubjectType::Provider, $progress->provider_id, (array) config('growth.fraud.incentive_hold_signal_types', []))) {
                $flags = $progress->flags ?? [];
                $this->flag($progress, $flags, FraudFlag::RiskSignalOpen, ['hold_signal_types' => true]);
                $this->incentives->updateProgress($progress, ['status' => IncentiveProgressStatus::Held, 'flags' => $flags]);
                $this->audit->record(AuditAction::StatusChanged, $progress, null, after: ['status' => IncentiveProgressStatus::Held->value, 'flag' => FraudFlag::RiskSignalOpen->value], meta: ['campaign' => $campaign->uuid]);
                break;
            }

            if ($campaign->bonus_minor > $campaign->remainingBudgetMinor()) {
                $this->incentives->updateProgress($progress, ['status' => IncentiveProgressStatus::Exhausted]);
                break;
            }

            $profile = $this->data->providerProfile($progress->provider_id);

            if (! $profile instanceof ProviderProfile) {
                break;
            }

            $sequence = $progress->awards_count + 1;
            $key = 'incentive:'.$progress->uuid.':'.$sequence;
            $award = $this->incentives->createAward([
                'progress_id' => $progress->id, 'campaign_id' => $campaign->id, 'provider_id' => $progress->provider_id, 'sequence' => $sequence,
                'amount_minor' => $campaign->bonus_minor, 'currency' => $campaign->currency, 'idempotency_key' => $key, 'granted_at' => now(),
            ]);
            $entry = $this->poster->grant($this->accounts->providerWallet($profile, $campaign->currency), $campaign->bonus_minor, JournalEntryType::IncentiveBonus, $key, 'incentive_award', $award->id, ['campaign' => $campaign->uuid, 'sequence' => $sequence]);
            $award->forceFill(['journal_entry_id' => $entry->id])->save();
            $this->incentives->updateCampaign($campaign, ['spent_minor' => $campaign->spent_minor + $campaign->bonus_minor]);
            $this->incentives->updateProgress($progress, ['awards_count' => $sequence, 'status' => IncentiveProgressStatus::InProgress]);
            $this->audit->record(AuditAction::Created, $award, null, after: ['amount_minor' => $campaign->bonus_minor, 'sequence' => $sequence], meta: ['campaign' => $campaign->uuid]);
            $paid++;
        }

        return $paid;
    }

    private function sharesDevice(ProviderProfile $profile, int $customerId): bool
    {
        if ($profile->user_id === $customerId) {
            return true;
        }

        return array_intersect($this->data->deviceIds($profile->user_id), $this->data->deviceIds($customerId)) !== [];
    }

    /**
     * @param list<array{flag: string, evidence: array<string, mixed>}> $flags
     * @param array<string, mixed> $evidence
     */
    private function flag(IncentiveProgress $progress, array &$flags, FraudFlag $flag, array $evidence): void
    {
        foreach ($flags as $existing) {
            if ($existing['flag'] === $flag->value) {
                return;
            }
        }

        $flags[] = ['flag' => $flag->value, 'evidence' => $evidence];
        $progress->flags = $flags;
    }
}
