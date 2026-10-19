<?php

namespace App\Services\Admin;

use App\DTOs\Admin\RoleData;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\Role;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Role business rules: transactional audited writes with before/after permission sets, optimistic locking,
 * protected system roles, and no privilege escalation (a non-Super Admin cannot add permissions they lack).
 */
final class RoleService
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    public function create(RoleData $data, Admin $actor): Role
    {
        $name = (string) $data->name;

        if ($this->roles->nameExists($name)) {
            throw BusinessRuleViolation::make('role_name_taken', 'name');
        }

        try {
            return $this->db->transaction(function () use ($data, $actor, $name): Role {
                $this->assertCanAdd($actor, $data->permissions, []);

                /** @var Role $role */
                $role = $this->roles->create(['name' => $name, 'guard_name' => 'admin', 'label' => $data->label, 'is_system' => false]);
                $this->roles->syncPermissions($role, $data->permissions);

                $this->audit->record(AuditAction::Created, $role, $actor, after: $role->auditSnapshot());

                return $role;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('role_name_taken', 'name');
        }
    }

    public function update(Role $role, RoleData $data, Admin $actor): Role
    {
        return $this->db->transaction(function () use ($role, $data, $actor): Role {
            $locked = $this->roles->lockById($role->getKey());

            if (! $locked instanceof Role) {
                throw (new ModelNotFoundException)->setModel(Role::class);
            }

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if ($locked->isSuperAdmin()) {
                throw BusinessRuleViolation::make('role_super_admin_protected');
            }

            $current = $this->roles->permissionNames($locked);
            $this->assertCanAdd($actor, $data->permissions, $current);

            $before = $locked->auditSnapshot();
            $attributes = ['label' => $data->label, 'lock_version' => $locked->lock_version + 1];

            // System role slugs are stable identifiers (seeders and policies refer to them).
            if (! $locked->is_system && $data->name !== null) {
                if ($this->roles->nameExists($data->name, (int) $locked->getKey())) {
                    throw BusinessRuleViolation::make('role_name_taken', 'name');
                }

                $attributes['name'] = $data->name;
            }

            $this->roles->update($locked, $attributes);
            $this->roles->syncPermissions($locked, $data->permissions);

            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->refresh()->auditSnapshot());

            return $locked;
        });
    }

    public function delete(Role $role, ?string $reason, Admin $actor): void
    {
        $this->db->transaction(function () use ($role, $reason, $actor): void {
            $locked = $this->roles->lockById($role->getKey());

            if (! $locked instanceof Role) {
                throw (new ModelNotFoundException)->setModel(Role::class);
            }

            if ($locked->is_system) {
                throw BusinessRuleViolation::make('role_system_protected');
            }

            if ($this->roles->adminCount($locked) > 0) {
                throw BusinessRuleViolation::make('role_in_use');
            }

            $before = $locked->auditSnapshot();
            $this->roles->delete($locked);

            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    /**
     * Permissions grouped by module for the permission matrix, plus the role's current selection.
     *
     * @return array{groups: array<string, list<AdminPermission>>, selected: list<string>}
     */
    public function matrix(?Role $role): array
    {
        return [
            'groups' => AdminPermission::grouped(),
            'selected' => $role === null ? [] : $this->roles->permissionNames($role),
        ];
    }

    /**
     * @param list<string> $requested
     * @param list<string> $current
     */
    private function assertCanAdd(Admin $actor, array $requested, array $current): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $own = $actor->getAllPermissions()->pluck('name')->all();

        if (array_diff(array_diff($requested, $current), $own) !== []) {
            throw BusinessRuleViolation::make('role_exceeds_own_permissions', 'permissions');
        }
    }
}
