<?php

namespace App\Repositories\Contracts\Admin;

use App\DTOs\Admin\RoleFilterData;
use App\Models\Role;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Role>
 */
interface RoleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * @return Builder<Role>
     */
    public function datatableQuery(RoleFilterData $filters): Builder;

    /**
     * Every role, system roles first, for selects and the admin form.
     *
     * @return Collection<int, Role>
     */
    public function allOrdered(): Collection;

    public function nameExists(string $name, ?int $exceptId = null): bool;

    /**
     * @param list<string> $permissionNames AdminPermission values (must already exist in the permissions table)
     */
    public function syncPermissions(Role $role, array $permissionNames): void;

    /**
     * @return list<string> permission names of the role, sorted
     */
    public function permissionNames(Role $role): array;

    /**
     * Number of admins (not soft-deleted) holding the role.
     */
    public function adminCount(Role $role): int;
}
