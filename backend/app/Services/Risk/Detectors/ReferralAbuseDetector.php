<?php

namespace App\Services\Risk\Detectors;

use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use Carbon\CarbonInterface;

/**
 * Placeholder: referrals arrive with P5.7, which fills this detector (device / phone / payment patterns of referral relationships).
 * Until `risk.referral_abuse.enabled` is switched on it raises nothing and closes nothing; no UI promises referral checks.
 */
final class ReferralAbuseDetector implements RiskDetector
{
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
        return false;
    }

    public function detect(CarbonInterface $since): array
    {
        return [];
    }
}
