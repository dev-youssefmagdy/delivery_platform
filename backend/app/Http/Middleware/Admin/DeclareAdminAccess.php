<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Explicit, greppable marker for admin routes that are not guarded by a permission: `admin.access:public`
 * (login, password reset: reachable without a session) or `admin.access:self` (any signed-in admin acting on
 * their own account). Every other admin route must carry `can:` (policy) middleware; the authorization guard
 * test fails for a route that has neither.
 */
final class DeclareAdminAccess
{
    public function handle(Request $request, Closure $next, string $kind): Response
    {
        return $next($request);
    }
}
