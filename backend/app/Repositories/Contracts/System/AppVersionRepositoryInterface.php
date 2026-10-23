<?php

namespace App\Repositories\Contracts\System;

use App\DTOs\System\AppVersionFilterData;
use App\Models\AppVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface AppVersionRepositoryInterface
{
    /**
     * @return Collection<int, AppVersion>
     */
    public function all(): Collection;

    public function lockById(int $id): ?AppVersion;

    public function exists(string $app, string $platform, ?int $exceptId = null): bool;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): AppVersion;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(AppVersion $version, array $attributes): AppVersion;

    /**
     * @return Builder<AppVersion>
     */
    public function datatableQuery(AppVersionFilterData $filters): Builder;
}
