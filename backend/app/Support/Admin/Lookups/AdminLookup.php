<?php

namespace App\Support\Admin\Lookups;

use App\DTOs\Admin\LookupOptionData;
use App\Enums\Admin\AdminPermission;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;

/**
 * Admin names (actor filters). Names only: no email or phone leaves the lookup.
 */
final class AdminLookup implements Lookup
{
    public function __construct(private readonly AdminRepositoryInterface $admins) {}

    public function permission(): ?AdminPermission
    {
        return null;
    }

    public function search(string $term, int $limit): array
    {
        return $this->map($this->admins->lookup($term, $limit)->all());
    }

    public function resolve(array $ids): array
    {
        return $this->map($this->admins->findManyByIds($ids)->all());
    }

    /**
     * @param array<int, Admin> $admins
     * @return list<LookupOptionData>
     */
    private function map(array $admins): array
    {
        return array_values(array_map(static fn (Admin $admin): LookupOptionData => new LookupOptionData($admin->id, $admin->name), $admins));
    }
}
