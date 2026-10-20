<?php

namespace App\Repositories\Eloquent\Identity;

use App\Models\CustomerProfile;
use App\Models\User;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;

final class CustomerProfileRepository implements CustomerProfileRepositoryInterface
{
    public function __construct(private readonly CustomerProfile $model) {}

    public function firstOrCreateFor(User $user): CustomerProfile
    {
        return $this->model->newQuery()->firstOrCreate(['user_id' => $user->id]);
    }

    public function update(CustomerProfile $profile, array $attributes): CustomerProfile
    {
        $profile->fill($attributes)->save();

        return $profile;
    }
}
