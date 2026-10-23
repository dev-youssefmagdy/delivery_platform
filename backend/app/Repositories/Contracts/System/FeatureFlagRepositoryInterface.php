<?php

namespace App\Repositories\Contracts\System;

use App\DTOs\System\FeatureFlagFilterData;
use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface FeatureFlagRepositoryInterface
{
    /**
     * @return Collection<string, FeatureFlag> keyed by flag key
     */
    public function allKeyed(): Collection;

    public function lockById(int $id): ?FeatureFlag;

    public function findByKey(string $key): ?FeatureFlag;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): FeatureFlag;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(FeatureFlag $flag, array $attributes): FeatureFlag;

    /**
     * @return Builder<FeatureFlag>
     */
    public function datatableQuery(FeatureFlagFilterData $filters): Builder;
}
