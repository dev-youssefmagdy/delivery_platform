<?php

namespace App\Http\Middleware\Admin;

use App\Models\Admin;
use App\Services\Admin\AdminSessionService;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after `auth:admin`: ends the session when the admin was suspended or deleted, or when the session was
 * revoked (sessions list, password reset, MFA reset); otherwise refreshes its last-activity time.
 */
final class EnsureAdminSession
{
    public function __construct(private readonly AdminSessionService $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if ($admin instanceof Admin) {
            $marked = (bool) $request->session()->get(AdminSessionService::MARKER, false);

            if (! $admin->isActive() || ! $this->sessions->validate($admin, $request->session()->getId(), $marked)) {
                auth('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw new AuthenticationException('Unauthenticated.', ['admin'], route('admin.login'));
            }
        }

        return $next($request);
    }
}
