<?php

namespace App\Repositories\Contracts\Settings;

use App\Models\PlatformSetting;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<PlatformSetting>
 */
interface SettingsRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Every stored override as key => decoded JSON value.
     *
     * @return array<string, mixed>
     */
    public function storedValues(): array;

    /**
     * Locking read of the given keys (SELECT … FOR UPDATE). Call inside a transaction.
     *
     * @param list<string> $keys
     * @return Collection<int, PlatformSetting>
     */
    public function lockByKeys(array $keys): Collection;

    /**
     * Creates or updates the override of one key; returns the row.
     */
    public function put(string $key, mixed $value, ?int $adminId): PlatformSetting;
}
