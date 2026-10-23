<?php

namespace App\Repositories\Eloquent\System;

use App\DTOs\System\AppVersionFilterData;
use App\Enums\System\AppPlatform;
use App\Enums\System\AppTarget;
use App\Models\AppVersion;
use App\Repositories\Contracts\System\AppVersionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AppVersionRepository implements AppVersionRepositoryInterface
{
    public function all(): Collection
    {
        return AppVersion::query()->orderBy('app')->orderBy('platform')->get();
    }

    public function lockById(int $id): ?AppVersion
    {
        return AppVersion::query()->whereKey($id)->lockForUpdate()->first();
    }

    public function exists(string $app, string $platform, ?int $exceptId = null): bool
    {
        return AppVersion::query()->where('app', $app)->where('platform', $platform)
            ->when($exceptId !== null, static fn (Builder $q) => $q->whereKeyNot($exceptId))->exists();
    }

    public function create(array $attributes): AppVersion
    {
        return AppVersion::query()->create($attributes);
    }

    public function update(AppVersion $version, array $attributes): AppVersion
    {
        $version->forceFill($attributes)->save();

        return $version;
    }

    public function datatableQuery(AppVersionFilterData $filters): Builder
    {
        return AppVersion::query()
            ->when($filters->apps !== [], static fn (Builder $q) => $q->whereIn('app_versions.app', array_map(static fn (AppTarget $c): string => $c->value, $filters->apps)))
            ->when($filters->platforms !== [], static fn (Builder $q) => $q->whereIn('app_versions.platform', array_map(static fn (AppPlatform $c): string => $c->value, $filters->platforms)))
            ->when($filters->forceUpdate !== null, static fn (Builder $q) => $q->where('app_versions.force_update', $filters->forceUpdate));
    }
}
