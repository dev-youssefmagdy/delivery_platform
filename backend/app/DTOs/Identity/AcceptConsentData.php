<?php

namespace App\DTOs\Identity;

final readonly class AcceptConsentData
{
    /**
     * @param list<string> $policyUuids ids of the policy versions the user accepted (as served by the API)
     */
    public function __construct(
        public array $policyUuids,
        public string $locale,
        public ?string $ip = null,
        public ?bool $marketingOptIn = null,
    ) {}
}
