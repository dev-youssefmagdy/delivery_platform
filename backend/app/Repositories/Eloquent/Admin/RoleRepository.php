<?php

namespace App\Repositories\Eloquent\Admin;

use App\DTOs\Admin\RoleFilterData;
use App\Models\Admin;
use App\Models\Role;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

/**
 * @extends BaseRepository<Role>
 */
final class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return parent::query()->where('guard_name', 'admin');
    }

    public function datatableQuery(RoleFilterData $filters): Builder
    {
        return $this->query()
            ->withCount('permissions')
            ->addSelect([
                'admins_count' => Admin::query()
                    ->selectRaw('count(*)')
                    ->join('model_has_roles', 'model_has_roles.model_id', '=', 'admins.id')
                    ->whereColumn('model_has_roles.role_id', 'roles.id')
                    ->where('model_has_roles.model_type', (new Admin)->getMorphClass()),
            ])
            ->when($filters->search, static function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(static fn (Builder $inner) => $inner
                    ->where('name', 'like', $like)
                    ->orWhere('label->ar', 'like', $like)
                    ->orWhere('label->en', 'like', $like));
            })
            ->when($filters->system !== null, static fn (Builder $query) => $query->where('is_system', $filters->system));
    }

    public function allOrdered(): Collection
    {
        return $this->query()->orderByDesc('is_system')->orderBy('name')->get();
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('name', $name)
            ->when($exceptId !== null, static fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public function syncPermissions(Role $role, array $permissionNames): void
    {
        $permissions = Permission::query()->where('guard_name', 'admin')->whereIn('name', $permissionNames)->get();
        $role->syncPermissions($permissions);
    }

    public function permissionNames(Role $role): array
    {
        return $role->permissions()->pluck('name')->sort()->values()->all();
    }

    public function adminCount(Role $role): int
    {
        return Admin::query()->role($role->name, 'admin')->count();
    }
}
