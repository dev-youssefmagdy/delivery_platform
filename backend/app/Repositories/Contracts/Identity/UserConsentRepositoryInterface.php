<?php

namespace App\Repositories\Contracts\Identity;

use App\Models\User;
use App\Models\UserConsent;

interface UserConsentRepositoryInterface
{
    /**
     * @return list<UserConsent> with the policy version loaded
     */
    public function forUser(User $user): array;

    /**
     * @return list<int> policy_versions.id the user accepted
     */
    public function acceptedPolicyIds(User $user): array;

    /**
     * Inserts the consent unless the user already accepted that version. True when a row was written.
     *
     * @param array<string, mixed> $attributes
     */
    public function createIfMissing(User $user, int $policyVersionId, array $attributes): bool;
}
