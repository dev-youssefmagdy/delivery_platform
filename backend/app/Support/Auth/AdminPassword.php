<?php

namespace App\Support\Auth;

use Illuminate\Validation\Rules\Password;

/**
 * Password policy for admin accounts (SRS §20): long, mixed case, digits and symbols. The compromised-password
 * check is enabled in production only (it calls an external range API).
 */
final class AdminPassword
{
    public static function rule(): Password
    {
        $rule = Password::min((int) config('admin.auth.password.min_length', 12))->mixedCase()->numbers()->symbols();

        return app()->isProduction() ? $rule->uncompromised() : $rule;
    }
}
