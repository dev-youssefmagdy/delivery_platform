<?php

namespace App\DTOs\Provider;

use App\Models\ProviderProfile;
use App\Models\User;

/**
 * Everything the provider show page needs, assembled by `ProviderService::details()`.
 */
final readonly class ProviderDetails
{
    public function __construct(
        public ProviderProfile $profile,
        public User $user,
        public bool $canRevealContact,
        public EligibilityResult $eligibility,
        public int $vehicleCount,
        public ?string $pendingDeletionRequestUuid,
    ) {}
}
