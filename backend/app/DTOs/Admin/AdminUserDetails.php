<?php

namespace App\DTOs\Admin;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Support\Collection;

/**
 * Everything the admin user show/edit screens display.
 */
final readonly class AdminUserDetails
{
    /**
     * @param Collection<int, Role> $roles
     * @param list<string> $permissionNames effective permissions (empty for Super Admin: it has all)
     */
    public function __construct(
        public Admin $admin,
        public Collection $roles,
        public array $permissionNames,
        public bool $isSuperAdmin,
    ) {}
}
