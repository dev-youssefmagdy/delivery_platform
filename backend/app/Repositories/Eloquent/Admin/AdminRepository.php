<?php

namespace App\Repositories\Eloquent\Admin;

use App\DTOs\Admin\AdminUserFilterData;
use App\Enums\Admin\AdminRole;
use App\Enums\Admin\AdminStatus;
use App\Models\Admin;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<Admin>
 */
final class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    public function datatableQuery(AdminUserFilterData $filters): Builder
    {
        return $this->query()
            ->with('roles:id,name,label')
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters->role, static fn (Builder $query, string $role) => $query->whereHas('roles', static fn (Builder $roles) => $roles->where('name', $role)))
            ->when($filters->statuses !== [], static fn (Builder $query) => $query->whereIn(
                'status',
                array_map(static fn (AdminStatus $status): string => $status->value, $filters->statuses),
            ))
            ->when($filters->mfaEnabled !== null, static fn (Builder $query) => $filters->mfaEnabled
                ? $query->whereNotNull('mfa_enabled_at')
                : $query->whereNull('mfa_enabled_at'))
            ->when($filters->createdFrom, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->createdTo, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    public function findByEmail(string $email): ?Admin
    {
        return $this->query()->where('email', mb_strtolower($email))->first();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        return $this->query()
            ->withTrashed()
            ->where('email', mb_strtolower($email))
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function syncRoles(Admin $admin, array $roleNames): void
    {
        $admin->syncRoles($roleNames);
    }

    public function countOtherActiveSuperAdminsLocked(int $exceptId): int
    {
        $ids = $this->query()
            ->whereKeyNot($exceptId)
            ->where('status', AdminStatus::Active->value)
            ->whereHas('roles', static fn (Builder $roles) => $roles->where('name', AdminRole::SuperAdmin->value))
            ->lockForUpdate()
            ->pluck('admins.id')
            ->all();

        return count($ids);
    }

    public function lookup(string $term, int $limit): Collection
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        return $this->query()
            ->when($term !== '', static fn (Builder $query) => $query->where('name', 'like', $like))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);
    }

    public function findManyByIds(array $ids): Collection
    {
        return $this->query()->whereKey($ids)->get(['id', 'name']);
    }

    public function loadDisplayRelations(Admin $admin): Admin
    {
        return $admin->loadMissing('roles.permissions');
    }
}
