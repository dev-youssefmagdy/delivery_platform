<?php

namespace App\Repositories\Contracts\Customers;

use App\Models\CustomerMessage;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<CustomerMessage>
 */
interface CustomerMessageRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<CustomerMessage> messages sent to the user with their sender, newest first
     */
    public function datatableQuery(User $user): Builder;

    public function deleteForUser(User $user): int;

    /**
     * @return Collection<int, CustomerMessage> the user's newest messages
     */
    public function latestForUser(User $user, int $limit): Collection;

    /**
     * Scoped to the owner: another user's message is "not found" (no IDOR).
     */
    public function findForUser(User $user, string $uuid): ?CustomerMessage;
}
