<?php

namespace App\Services\Admin;

use App\DTOs\Admin\AdminUserData;
use App\DTOs\Admin\AdminUserDetails;
use App\Enums\Admin\AdminRole;
use App\Enums\Admin\AdminStatus;
use App\Enums\Common\AuditAction;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Notifications\Admin\AdminPasswordNotification;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

/**
 * Admin user business rules (SRS §4.2, §16.16): transactional audited writes, optimistic locking, and the
 * safety rules around privileged access:
 *  - only a Super Admin may grant or remove the Super Admin role;
 *  - other admins may only grant roles whose permissions they hold themselves (no privilege escalation);
 *  - nobody suspends, deletes or demotes themselves, and the last active Super Admin can never be removed.
 */
final class AdminUserService
{
    public function __construct(
        private readonly AdminRepositoryInterface $admins,
        private readonly RoleRepositoryInterface $roles,
        private readonly AdminSessionService $sessions,
        private readonly AdminMfaService $mfa,
        private readonly AdminPasswordService $passwords,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Creates the admin with an unusable random password and emails an invitation link to set their own.
     */
    public function create(AdminUserData $data, Admin $actor): Admin
    {
        if ($this->admins->emailExists($data->email)) {
            throw BusinessRuleViolation::make('email_taken', 'email');
        }

        try {
            $admin = $this->db->transaction(function () use ($data, $actor): Admin {
                $this->assertCanGrant($actor, $data->roles, []);

                /** @var Admin $admin */
                $admin = $this->admins->create($data->attributes() + ['password' => Str::random(64)]);
                $this->admins->syncRoles($admin, $data->roles);

                $this->audit->record(AuditAction::Created, $admin, $actor, after: $admin->auditSnapshot());

                return $admin;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('email_taken', 'email');
        }

        $admin->notify(new AdminPasswordNotification($this->passwords->createInvitationToken($admin), invited: true));

        return $admin;
    }

    public function update(Admin $admin, AdminUserData $data, Admin $actor): Admin
    {
        try {
            return $this->db->transaction(function () use ($admin, $data, $actor): Admin {
                $locked = $this->lock($admin);

                if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                    throw ConflictException::make('stale_version');
                }

                if ($this->admins->emailExists($data->email, (int) $locked->getKey())) {
                    throw BusinessRuleViolation::make('email_taken', 'email');
                }

                $currentRoles = $locked->getRoleNames()->all();
                $this->assertCanChangeRoles($actor, $locked, $currentRoles, $data->roles);

                $before = $locked->auditSnapshot();
                $this->admins->update($locked, $data->attributes() + ['lock_version' => $locked->lock_version + 1]);
                $this->admins->syncRoles($locked, $data->roles);

                $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->refresh()->auditSnapshot());

                return $locked;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('email_taken', 'email');
        }
    }

    public function suspend(Admin $admin, string $reason, Admin $actor): Admin
    {
        return $this->db->transaction(function () use ($admin, $reason, $actor): Admin {
            $locked = $this->lock($admin);
            $this->assertNotSelf($actor, $locked);

            if ($locked->status === AdminStatus::Suspended) {
                throw BusinessRuleViolation::make('admin_already_suspended');
            }

            $this->assertNotLastSuperAdmin($locked);

            $this->admins->update($locked, ['status' => AdminStatus::Suspended, 'suspended_at' => now(), 'lock_version' => $locked->lock_version + 1]);
            $this->sessions->revokeAll($locked);

            $this->audit->record(AuditAction::Suspended, $locked, $actor, ['status' => AdminStatus::Active->value], ['status' => AdminStatus::Suspended->value], $reason);

            return $locked;
        });
    }

    public function reactivate(Admin $admin, ?string $reason, Admin $actor): Admin
    {
        return $this->db->transaction(function () use ($admin, $reason, $actor): Admin {
            $locked = $this->lock($admin);

            if ($locked->status === AdminStatus::Active) {
                throw BusinessRuleViolation::make('admin_already_active');
            }

            $this->admins->update($locked, ['status' => AdminStatus::Active, 'suspended_at' => null, 'lock_version' => $locked->lock_version + 1]);

            $this->audit->record(AuditAction::Reactivated, $locked, $actor, ['status' => AdminStatus::Suspended->value], ['status' => AdminStatus::Active->value], $reason);

            return $locked;
        });
    }

    public function resetMfa(Admin $admin, string $reason, Admin $actor): void
    {
        $this->assertNotSelf($actor, $admin);
        $this->mfa->reset($admin, $reason, $actor);
    }

    public function delete(Admin $admin, ?string $reason, Admin $actor): void
    {
        $this->db->transaction(function () use ($admin, $reason, $actor): void {
            $locked = $this->lock($admin);
            $this->assertNotSelf($actor, $locked);
            $this->assertNotLastSuperAdmin($locked);

            $before = $locked->auditSnapshot();
            $this->sessions->revokeAll($locked);
            $this->admins->delete($locked);

            $this->audit->record(AuditAction::Deleted, $locked, $actor, before: $before, reason: $reason);
        });
    }

    public function details(Admin $admin): AdminUserDetails
    {
        $admin = $this->admins->loadDisplayRelations($admin);
        $isSuperAdmin = $admin->isSuperAdmin();

        return new AdminUserDetails(
            $admin,
            $admin->roles,
            $isSuperAdmin ? [] : $admin->getAllPermissions()->pluck('name')->sort()->values()->all(),
            $isSuperAdmin,
        );
    }

    private function lock(Admin $admin): Admin
    {
        $locked = $this->admins->lockById($admin->getKey());

        return $locked instanceof Admin ? $locked : throw (new ModelNotFoundException)->setModel(Admin::class);
    }

    private function assertNotSelf(Admin $actor, Admin $target): void
    {
        if ($actor->is($target)) {
            throw BusinessRuleViolation::make('cannot_modify_self');
        }
    }

    /**
     * The system must always keep one active Super Admin.
     */
    private function assertNotLastSuperAdmin(Admin $target): void
    {
        if ($target->isSuperAdmin() && $target->isActive() && $this->admins->countOtherActiveSuperAdminsLocked((int) $target->getKey()) === 0) {
            throw BusinessRuleViolation::make('last_super_admin');
        }
    }

    /**
     * @param list<string> $currentRoles
     * @param list<string> $newRoles
     */
    private function assertCanChangeRoles(Admin $actor, Admin $target, array $currentRoles, array $newRoles): void
    {
        $super = AdminRole::SuperAdmin->value;
        $removesSuper = in_array($super, $currentRoles, true) && ! in_array($super, $newRoles, true);

        if ($removesSuper) {
            if ($actor->is($target)) {
                throw BusinessRuleViolation::make('cannot_remove_own_super_admin', 'roles');
            }

            $this->assertNotLastSuperAdmin($target);
        }

        $this->assertCanGrant($actor, $newRoles, $currentRoles, $removesSuper);
    }

    /**
     * @param list<string> $newRoles
     * @param list<string> $currentRoles
     */
    private function assertCanGrant(Admin $actor, array $newRoles, array $currentRoles, bool $removesSuper = false): void
    {
        $added = array_values(array_diff($newRoles, $currentRoles));

        if ($actor->isSuperAdmin()) {
            return;
        }

        if ($removesSuper || in_array(AdminRole::SuperAdmin->value, $added, true)) {
            throw BusinessRuleViolation::make('super_admin_only', 'roles');
        }

        $own = $actor->getAllPermissions()->pluck('name')->all();

        foreach ($this->roles->query()->whereIn('name', $added)->with('permissions:id,name')->get() as $role) {
            if (array_diff($role->permissions->pluck('name')->all(), $own) !== []) {
                throw BusinessRuleViolation::make('role_exceeds_own_permissions', 'roles');
            }
        }
    }
}
