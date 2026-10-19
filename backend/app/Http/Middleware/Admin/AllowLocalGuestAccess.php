<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEMPORARY scaffolding until admin authentication (P1.1): in the `local` environment, with
 * ADMIN_LOCAL_GUEST_ACCESS=true, guests pass every Gate check on admin routes so the UI kit can be used in a
 * browser. Inert in every other environment (including testing). P1.1 deletes this middleware.
 */
final class AllowLocalGuestAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && config('admin.local_guest_access') === true && $request->user() === null) {
            Gate::before(static fn (?Authenticatable $user): ?bool => $user === null ? true : null);
        }

        return $next($request);
    }
}
