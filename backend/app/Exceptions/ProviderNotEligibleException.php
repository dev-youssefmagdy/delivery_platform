<?php

namespace App\Exceptions;

use App\DTOs\Provider\EligibilityIssue;
use App\DTOs\Provider\EligibilityResult;

/**
 * 422 `provider_not_eligible`: the typed eligibility reasons travel in `meta.reasons` so the app can show each one.
 */
class ProviderNotEligibleException extends BusinessRuleViolation
{
    public static function because(EligibilityResult $result): static
    {
        $exception = static::make('provider_not_eligible', null, [
            'reasons' => array_map(static fn (EligibilityIssue $issue): array => [
                'reason' => $issue->reason->value,
                'reference' => $issue->reference,
            ], $result->issues),
        ]);

        return $exception;
    }

    public function meta(): array
    {
        return ['reasons' => $this->context['reasons'] ?? []];
    }
}
