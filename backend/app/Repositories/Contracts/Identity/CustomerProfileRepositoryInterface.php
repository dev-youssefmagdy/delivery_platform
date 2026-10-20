<?php

namespace App\Repositories\Contracts\Identity;

use App\Models\CustomerProfile;
use App\Models\User;

interface CustomerProfileRepositoryInterface
{
    public function firstOrCreateFor(User $user): CustomerProfile;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(CustomerProfile $profile, array $attributes): CustomerProfile;

    /**
     * Clears every personal attribute of the user's profile (anonymisation); the row stays.
     */
    public function scrub(User $user): void;
}
