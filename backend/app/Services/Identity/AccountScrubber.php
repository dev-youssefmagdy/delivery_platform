<?php

namespace App\Services\Identity;

use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use App\Repositories\Contracts\Identity\AccessTokenRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;

/**
 * The account-level steps of anonymisation shared by the customer and provider deletion flows (ADR-75): tokens,
 * devices and push tokens, saved places, help messages, customer profile contents, and the user row itself
 * (name, email, phone, photo reference removed; the row, uuid and financial references stay). Callers run it inside
 * their transaction after their own checks, and delete the returned photo file after the commit.
 */
final class AccountScrubber
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly AccessTokenRepositoryInterface $tokens,
        private readonly UserDeviceRepositoryInterface $devices,
        private readonly SavedPlaceRepositoryInterface $savedPlaces,
        private readonly CustomerMessageRepositoryInterface $messages,
        private readonly CustomerProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return string|null the profile photo path to delete from storage after commit
     */
    public function scrub(User $locked): ?string
    {
        $photoPath = $locked->photo_path;

        $this->tokens->revokeAll($locked);
        $this->devices->deleteForUser($locked);
        $this->savedPlaces->deleteForUser($locked->id);
        $this->messages->deleteForUser($locked);
        $this->profiles->scrub($locked);
        $this->customers->anonymise($locked);

        return $photoPath;
    }
}
