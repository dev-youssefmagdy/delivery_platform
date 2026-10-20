<?php

namespace App\DTOs\Customers;

use App\Models\AccountDeletionRequest;
use App\Models\CustomerProfile;
use App\Models\User;

/**
 * Everything the customer show page needs. Contact details (phone, email, emergency contact phone) are always masked
 * here; revealing them is a separate, audited request that needs `customers.view_pii` (`canRevealContact`).
 */
final readonly class CustomerDetails
{
    public function __construct(
        public User $user,
        public ?CustomerProfile $profile,
        public ?AccountDeletionRequest $pendingDeletion,
        public string $maskedPhone,
        public ?string $maskedEmail,
        public ?string $maskedEmergencyPhone,
        public bool $canRevealContact,
    ) {}
}
