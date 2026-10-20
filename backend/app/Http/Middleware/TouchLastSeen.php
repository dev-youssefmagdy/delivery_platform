<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `user.seen` route middleware: refreshes `users.last_seen_at` at most every `identity.last_seen_interval_seconds`.
 */
final class TouchLastSeen
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->users->touchLastSeen($user, (int) config('identity.last_seen_interval_seconds', 300));
        }

        return $next($request);
    }
}
