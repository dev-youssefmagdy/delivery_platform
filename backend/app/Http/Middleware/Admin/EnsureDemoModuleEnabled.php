<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Demo Item proof module exists only in local/testing with ADMIN_DEMO_ENABLED=true (404 otherwise),
 * even if routes were cached elsewhere.
 */
final class EnsureDemoModuleEnabled
{
    public static function enabled(): bool
    {
        return config('admin.demo.enabled') === true && app()->environment((array) config('admin.demo.environments', []));
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(self::enabled(), 404);

        return $next($request);
    }
}
