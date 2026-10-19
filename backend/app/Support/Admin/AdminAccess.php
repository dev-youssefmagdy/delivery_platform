<?php

namespace App\Support\Admin;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;

/**
 * Checks a raw permission name (AdminPermission value) for the current admin. Authenticated admins answer via
 * their own can() (Spatie from P1.1); guests only pass through Gate::before (the temporary local scaffolding).
 */
final class AdminAccess
{
    public static function allows(string ...$permissions): bool
    {
        $user = auth()->user();

        foreach ($permissions as $permission) {
            if ($user instanceof Authorizable ? $user->can($permission) : Gate::allows($permission)) {
                return true;
            }
        }

        return false;
    }
}
