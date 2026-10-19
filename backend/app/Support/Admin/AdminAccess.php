<?php

namespace App\Support\Admin;

use App\Models\Admin;

/**
 * Checks raw permission names (AdminPermission values) for the signed-in admin; guests have no permissions.
 * Super Admin passes through Gate::before, everybody else through Spatie.
 */
final class AdminAccess
{
    public static function allows(string ...$permissions): bool
    {
        $admin = auth('admin')->user();

        if (! $admin instanceof Admin) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($admin->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
