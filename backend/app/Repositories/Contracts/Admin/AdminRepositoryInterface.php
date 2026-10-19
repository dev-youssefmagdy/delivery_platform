<?php

namespace App\Repositories\Contracts\Admin;

use App\DTOs\Admin\AdminUserFilterData;
use App\Models\Admin;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Admin>
 */
interface AdminRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Listing query with the filters card applied (roles eager loaded).
     *
     * @return Builder<Admin>
     */
    public function datatableQuery(AdminUserFilterData $filters): Builder;

    public function findByEmail(string $email): ?Admin;

    /**
     * Whether an email is taken, including soft-deleted admins (the unique index covers them).
     */
    public function emailExists(string $email, ?int $exceptId = null): bool;

    /**
     * @param list<string> $roleNames roles.name values (guard admin)
     */
    public function syncRoles(Admin $admin, array $roleNames): void;

    /**
     * Number of active, non-deleted Super Admins other than $exceptId, read under a row lock so concurrent
     * demotions cannot remove the last one.
     */
    public function countOtherActiveSuperAdminsLocked(int $exceptId): int;

    /**
     * Name search for remote selects.
     *
     * @return Collection<int, Admin>
     */
    public function lookup(string $term, int $limit): Collection;

    /**
     * @param list<int|string> $ids
     * @return Collection<int, Admin>
     */
    public function findManyByIds(array $ids): Collection;

    /**
     * Loads what the detail screens display (roles + permissions).
     */
    public function loadDisplayRelations(Admin $admin): Admin;
}
