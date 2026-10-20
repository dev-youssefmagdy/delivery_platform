<?php

namespace App\DTOs\Provider;

use App\Enums\Provider\EligibilityReason;

/**
 * Outcome of `ProviderEligibilityService::check()`. `usableVehicleIds` are the approved vehicles with all required
 * documents valid; dispatch and the online toggle (P2.2) choose among them.
 */
final readonly class EligibilityResult
{
    /**
     * @param list<EligibilityIssue> $issues
     * @param list<int> $usableVehicleIds
     */
    public function __construct(
        public array $issues,
        public array $usableVehicleIds = [],
    ) {}

    public function isEligible(): bool
    {
        return $this->issues === [];
    }

    /**
     * @return list<EligibilityReason>
     */
    public function reasons(): array
    {
        $reasons = [];

        foreach ($this->issues as $issue) {
            if (! in_array($issue->reason, $reasons, true)) {
                $reasons[] = $issue->reason;
            }
        }

        return $reasons;
    }

    public function has(EligibilityReason $reason): bool
    {
        return in_array($reason, $this->reasons(), true);
    }
}
