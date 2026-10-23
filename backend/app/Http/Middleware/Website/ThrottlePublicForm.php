<?php

namespace App\Http\Middleware\Website;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttles the public website forms. The real submit uses the `public-forms` limiter (a few per minute per IP); the
 * validate-before-submit (Precognition) calls that come before it use their own, more generous `website-precognition` limiter,
 * so one human submission does not eat two of the few allowed attempts.
 */
final class ThrottlePublicForm
{
    public function __construct(private readonly ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $this->throttle->handle($request, $next, $request->isPrecognitive() ? 'website-precognition' : 'public-forms');
    }
}
