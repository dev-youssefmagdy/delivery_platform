<?php

namespace App\DTOs\Identity;

use App\Models\PolicyVersion;
use App\Models\UserConsent;

/**
 * The policy versions an app requires right now, which of them the user still has to accept, and what the user
 * has accepted so far.
 */
final readonly class ConsentStatus
{
    /**
     * @param list<PolicyVersion> $required current published versions of the types the app requires
     * @param list<PolicyVersion> $pending subset of $required that is not accepted yet
     * @param list<UserConsent> $accepted
     */
    public function __construct(
        public array $required,
        public array $pending,
        public array $accepted,
    ) {}
}
