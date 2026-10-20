<?php

namespace App\Repositories\Contracts\Identity;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

interface AccountDeletionRequestRepositoryInterface
{
    public function pendingFor(User $user): ?AccountDeletionRequest;

    /**
     * Scoped to the owner: another user's request is "not found" (no IDOR).
     */
    public function findForUser(User $user, string $uuid): ?AccountDeletionRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): AccountDeletionRequest;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(AccountDeletionRequest $request, array $attributes): AccountDeletionRequest;

    /**
     * @return Builder<AccountDeletionRequest> every request of the user, newest first (admin customer tab)
     */
    public function datatableQuery(User $user): Builder;
}
