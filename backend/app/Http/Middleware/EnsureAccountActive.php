<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenActionException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `account.active` route middleware: blocks mobile accounts that may not use business endpoints, with a typed
 * 403 code per UserStatus (`account_suspended`, `account_deleted`, `account_deletion_pending`,
 * `account_verification_required`, and `account_restricted` with the `strict` parameter, for example on booking
 * creation). Status-safe routes (GET /me, logout, cancel deletion) simply do not use it.
 */
final class EnsureAccountActive
{
    public function handle(Request $request, Closure $next, string $mode = ''): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $code = $user->status->blockCode(strict: $mode === 'strict');

            if ($code !== null) {
                throw ForbiddenActionException::make($code);
            }
        }

        return $next($request);
    }
}
