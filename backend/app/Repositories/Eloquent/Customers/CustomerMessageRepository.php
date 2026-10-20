<?php

namespace App\Repositories\Eloquent\Customers;

use App\Models\CustomerMessage;
use App\Models\User;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<CustomerMessage>
 */
final class CustomerMessageRepository extends BaseRepository implements CustomerMessageRepositoryInterface
{
    public function __construct(CustomerMessage $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(User $user): Builder
    {
        return $this->query()->with('sender:id,name')->where('user_id', $user->id);
    }

    public function deleteForUser(User $user): int
    {
        return $this->query()->where('user_id', $user->id)->delete();
    }

    public function latestForUser(User $user, int $limit): Collection
    {
        return $this->query()->where('user_id', $user->id)->orderByDesc('id')->limit($limit)->get();
    }

    public function findForUser(User $user, string $uuid): ?CustomerMessage
    {
        return $this->query()->where('user_id', $user->id)->where('uuid', $uuid)->first();
    }
}
