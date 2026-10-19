<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes `admin` the default guard for every request under the admin path, so `auth()->user()`,
 * `$request->user()`, Gate checks and the locale preference all refer to the admin. Runs first in the web group.
 */
final class UseAdminGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = (string) config('admin.path');

        if ($request->is($path, $path.'/*')) {
            Auth::shouldUse('admin');
        }

        return $next($request);
    }
}
