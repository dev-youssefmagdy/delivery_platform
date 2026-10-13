<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accepts a safe inbound `X-Request-Id` or generates a UUID, shares it through Laravel Context (so it lands in
 * every log line, queued job and the error envelope) and echoes it on the response (Plan §4.4, SRS §23.4).
 * The client IP and user agent are kept as hidden context (not logged) for the audit logger.
 */
final class CorrelationId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolve($request->headers->get(self::HEADER));

        $request->headers->set(self::HEADER, $requestId);
        Context::add('request_id', $requestId);
        Context::addHidden('ip', $request->ip());
        Context::addHidden('user_agent', Str::limit((string) $request->userAgent(), 512, ''));

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }

    private function resolve(?string $inbound): string
    {
        if ($inbound !== null && preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $inbound) === 1) {
            return $inbound;
        }

        return (string) Str::uuid();
    }
}
