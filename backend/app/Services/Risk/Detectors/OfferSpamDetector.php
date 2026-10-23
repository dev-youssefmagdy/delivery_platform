<?php

namespace App\Services\Risk\Detectors;

use App\DTOs\Risk\RiskCandidate;
use App\Enums\Risk\RiskSignalType;
use App\Enums\Risk\RiskSubjectType;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use App\Services\Risk\RiskScore;
use Carbon\CarbonInterface;

/**
 * A provider who sends a very large number of offers of which almost none is accepted (blasting offers to every request).
 */
final class OfferSpamDetector implements RiskDetector
{
    public function __construct(private readonly RiskDataRepositoryInterface $data) {}

    public function type(): RiskSignalType
    {
        return RiskSignalType::OfferSpam;
    }

    public function subjectTypes(): array
    {
        return [RiskSubjectType::Provider];
    }

    public function enabled(): bool
    {
        return true;
    }

    public function detect(CarbonInterface $since): array
    {
        $min = max(1, (int) config('risk.offer_spam.min_offers', 40));
        $maxAccept = max(0, (int) config('risk.offer_spam.max_accept_percent', 5));
        $candidates = [];

        foreach ($this->data->offerStats($since, $min) as $row) {
            $acceptPercent = (int) round($row['accepted'] * 100 / max(1, $row['offers']));

            if ($acceptPercent > $maxAccept) {
                continue;
            }

            $candidates[] = new RiskCandidate($this->type(), RiskSubjectType::Provider, $row['subject_id'], RiskScore::scale($row['offers'], $min, $min * 4), [
                'window_days' => (int) config('risk.window_days', 30), 'offers' => $row['offers'], 'accepted' => $row['accepted'], 'withdrawn' => $row['withdrawn'],
                'revisions' => $row['revisions'], 'accept_percent' => $acceptPercent, 'min_offers' => $min, 'max_accept_percent' => $maxAccept,
            ]);
        }

        return $candidates;
    }
}
