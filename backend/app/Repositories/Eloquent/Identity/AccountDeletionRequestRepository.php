<?php

namespace App\Repositories\Eloquent\Identity;

use App\Enums\Identity\DeletionRequestStatus;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class AccountDeletionRequestRepository implements AccountDeletionRequestRepositoryInterface
{
    public function __construct(private readonly AccountDeletionRequest $model) {}

    public function pendingFor(User $user): ?AccountDeletionRequest
    {
        return $this->model->newQuery()
            ->where('user_id', $user->id)
            ->where('status', DeletionRequestStatus::Pending->value)
            ->latest('id')
            ->first();
    }

    public function findForUser(User $user, string $uuid): ?AccountDeletionRequest
    {
        return $this->model->newQuery()->where('user_id', $user->id)->where('uuid', $uuid)->first();
    }

    public function create(array $attributes): AccountDeletionRequest
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function update(AccountDeletionRequest $request, array $attributes): AccountDeletionRequest
    {
        $request->fill($attributes)->save();

        return $request;
    }

    public function datatableQuery(User $user): Builder
    {
        return $this->model->newQuery()->where('user_id', $user->id);
    }
}
