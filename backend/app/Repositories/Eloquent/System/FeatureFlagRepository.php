<?php

namespace App\Repositories\Eloquent\System;

use App\DTOs\System\FeatureFlagFilterData;
use App\Models\FeatureFlag;
use App\Repositories\Contracts\System\FeatureFlagRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FeatureFlagRepository implements FeatureFlagRepositoryInterface
{
    public function allKeyed(): Collection
    {
        return FeatureFlag::query()->get()->keyBy(static fn (FeatureFlag $flag): string => $flag->key->value);
    }

    public function lockById(int $id): ?FeatureFlag
    {
        return FeatureFlag::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function findByKey(string $key): ?FeatureFlag
    {
        return FeatureFlag::query()->where('key', $key)->first();
    }

    public function create(array $attributes): FeatureFlag
    {
        return FeatureFlag::query()->create($attributes);
    }

    public function update(FeatureFlag $flag, array $attributes): FeatureFlag
    {
        $flag->forceFill($attributes)->save();

        return $flag;
    }

    public function datatableQuery(FeatureFlagFilterData $filters): Builder
    {
        return FeatureFlag::query()
            ->when($filters->key !== null, static fn (Builder $q) => $q->where('feature_flags.key', 'like', '%'.addcslashes($filters->key ?? '', '%_\\').'%'))
            ->when($filters->enabled !== null, static fn (Builder $q) => $q->where('feature_flags.enabled', $filters->enabled));
    }
}
