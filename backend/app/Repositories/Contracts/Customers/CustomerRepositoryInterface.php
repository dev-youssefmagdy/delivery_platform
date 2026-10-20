<?php

namespace App\Repositories\Contracts\Customers;

use App\DTOs\Customers\CustomerFilterData;
use App\Enums\Identity\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Customer accounts: users that have a customer profile. Soft-deleted (anonymised) accounts stay readable because
 * bookings and payments keep referencing them.
 *
 * @extends BaseRepositoryInterface<User>
 */
interface CustomerRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<User>
     */
    public function datatableQuery(CustomerFilterData $filters): Builder;

    public function findByUuidWithTrashed(string $uuid): ?User;

    /**
     * Row lock (inside a transaction), trashed accounts included.
     */
    public function lockForUpdate(int $id): ?User;

    /**
     * @return Collection<int, User> suspended customers whose suspension ended at or before $now
     */
    public function suspensionsEndedBefore(CarbonInterface $now, int $limit): Collection;

    /**
     * Writes the account status together with its reason and optional end (clears both for plain statuses).
     */
    public function applyStatus(User $user, UserStatus $status, ?string $reason, ?CarbonInterface $expiresAt): User;

    /**
     * Removes every personal attribute from the account, soft-deletes it and marks the status `deleted`.
     */
    public function anonymise(User $user): User;

    /**
     * True when the account also has a provider profile (such accounts are handled by the provider module).
     */
    public function hasProviderProfile(User $user): bool;
}
