<?php

namespace App\Http\Middleware\Admin;

use App\Exceptions\ForbiddenActionException;
use App\Models\Admin;
use App\Services\Admin\AdminMfaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins whose role requires MFA (config admin.auth.mfa.required_roles) can only reach the security screens
 * until they have enrolled.
 */
final class EnforceAdminMfa
{
    public function __construct(private readonly AdminMfaService $mfa) {}

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if ($admin instanceof Admin && ! $admin->hasMfaEnabled() && $this->mfa->isRequiredFor($admin) && ! $request->routeIs('admin.security.*', 'admin.logout', 'admin.locale.update')) {
            if ($request->expectsJson()) {
                throw ForbiddenActionException::make('mfa_enrolment_required');
            }

            return redirect()->route('admin.security.mfa');
        }

        return $next($request);
    }
}
