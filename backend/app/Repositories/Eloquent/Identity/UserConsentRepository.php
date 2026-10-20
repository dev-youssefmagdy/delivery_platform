<?php

namespace App\Repositories\Eloquent\Identity;

use App\Models\User;
use App\Models\UserConsent;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

final class UserConsentRepository implements UserConsentRepositoryInterface
{
    public function __construct(private readonly UserConsent $model) {}

    public function forUser(User $user): array
    {
        return $this->model->newQuery()
            ->with('policyVersion')
            ->where('user_id', $user->id)
            ->orderByDesc('accepted_at')
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function acceptedPolicyIds(User $user): array
    {
        return array_map(
            'intval',
            $this->model->newQuery()->where('user_id', $user->id)->pluck('policy_version_id')->all(),
        );
    }

    public function createIfMissing(User $user, int $policyVersionId, array $attributes): bool
    {
        return $this->model->newQuery()->insertOrIgnore([
            'user_id' => $user->id,
            'policy_version_id' => $policyVersionId,
            ...$attributes,
        ]) > 0;
    }

    public function datatableQuery(User $user): Builder
    {
        return $this->model->newQuery()->with('policyVersion')->where('user_id', $user->id);
    }
}
