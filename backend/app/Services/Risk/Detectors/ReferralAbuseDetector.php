<?php

namespace App\Services\Risk\Detectors;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Growth\ReferralAudience;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Growth\IncentiveRepositoryInterface;
use App\Repositories\Contracts\Growth\ReferralRepositoryInterface;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use App\Services\Risk\RiskScore;
use Carbon\CarbonInterface;

/**
 * Referral and incentive abuse (P5.7): every referral relationship the growth fraud checks put under review, and every provider quest
 * progress held for review, becomes a risk signal about the person who would be paid (the referrer, the provider). The signal is a task
 * for a person in the risk queue; nothing here restricts an account. Signals close themselves when the review decides the case (the
 * relationship is approved / rejected, the progress released / rejected). Evidence is counts and flag names, never contact data.
 */
final class ReferralAbuseDetector implements RiskDetector
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly IncentiveRepositoryInterface $incentives,
        private readonly RiskDataRepositoryInterface $data,
    ) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::ReferralAbuse;
    }

    public function subjectTypes(): array
    {
        return [RiskSubjectType::Customer, RiskSubjectType::Provider];
    }

    public function enabled(): bool
    {
        return (bool) config('risk.referral_abuse.enabled', true);
    }

    public function detect(CarbonInterface $since): array
    {
        /** @var array<string, array{subject: RiskSubjectType, id: int, referrals: int, incentives: int, flags: list<string>}> $merged */
        $merged = [];

        foreach ($this->referrals->underReview() as $relationship) {
            $subject = $relationship->audience === ReferralAudience::Provider ? RiskSubjectType::Provider : RiskSubjectType::Customer;
            $id = $subject === RiskSubjectType::Provider ? $this->data->providerProfileIdOfUser($relationship->referrer_user_id) : $relationship->referrer_user_id;

            if ($id === null) {
                continue;
            }

            $key = $subject->value.':'.$id;
            $merged[$key] ??= ['subject' => $subject, 'id' => $id, 'referrals' => 0, 'incentives' => 0, 'flags' => []];
            $merged[$key]['referrals']++;
            $merged[$key]['flags'] = array_values(array_unique(array_merge($merged[$key]['flags'], $relationship->flagKeys())));
        }

        foreach ($this->incentives->held() as $progress) {
            $key = RiskSubjectType::Provider->value.':'.$progress->provider_id;
            $merged[$key] ??= ['subject' => RiskSubjectType::Provider, 'id' => $progress->provider_id, 'referrals' => 0, 'incentives' => 0, 'flags' => []];
            $merged[$key]['incentives']++;
            $merged[$key]['flags'] = array_values(array_unique(array_merge($merged[$key]['flags'], array_map(static fn (array $flag): string => $flag['flag'], $progress->flags ?? []))));
        }

        $candidates = [];

        foreach ($merged as $row) {
            $count = $row['referrals'] + $row['incentives'];
            $candidates[] = new RiskCandidate($this->type(), $row['subject'], $row['id'], max(RiskScore::AT_THRESHOLD, RiskScore::scale($count, 1, 5)), [
                'referrals_under_review' => $row['referrals'], 'incentive_progress_held' => $row['incentives'], 'flags' => $row['flags'],
            ]);
        }

        return $candidates;
    }
}
