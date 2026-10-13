<?php

namespace App\Exceptions;

use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every exception into the ADR-14 envelope for JSON/AJAX requests. Never leaks SQL, traces,
 * file paths or exception messages of unexpected errors (SRS §20.7). HTML requests return null so Laravel
 * renders its normal views.
 */
final class ApiExceptionRenderer
{
    /** @var array<int, string> */
    private const STATUS_CODES = [
        401 => 'unauthenticated',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        419 => 'session_expired',
        429 => 'too_many_requests',
        503 => 'service_unavailable',
    ];

    public static function wantsEnvelope(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! self::wantsEnvelope($request) || $e instanceof HttpResponseException) {
            return null;
        }

        return match (true) {
            $e instanceof DomainException => ApiResponse::error(
                $e->getMessage(), $e->getStatusCode(), $e->errorCode, $e->fieldErrors(), headers: $e->getHeaders(),
            ),
            $e instanceof ValidationException => ApiResponse::error(
                (string) __('api.errors.validation_failed'), $e->status, 'validation_failed', $e->errors(),
            ),
            $e instanceof AuthenticationException => ApiResponse::error(
                (string) __('api.errors.unauthenticated'), 401, 'unauthenticated',
            ),
            $e instanceof AuthorizationException => $this->http($e->status() ?? 403),
            $e instanceof ModelNotFoundException => $this->http(404),
            $e instanceof ThrottleRequestsException => $this->throttled($e),
            $e instanceof HttpExceptionInterface => $this->http($e->getStatusCode(), $e->getHeaders()),
            default => ApiResponse::error((string) __('api.errors.server_error'), 500, 'server_error'),
        };
    }

    /**
     * @param array<string, string> $headers
     */
    private function http(int $status, array $headers = []): JsonResponse
    {
        $code = self::STATUS_CODES[$status] ?? "http_{$status}";
        $key = "api.errors.{$code}";
        $message = __($key);

        return ApiResponse::error(
            is_string($message) && $message !== $key ? $message : (string) __('api.http.'.($status >= 500 ? 500 : 400)),
            $status,
            $code,
            headers: $headers,
        );
    }

    private function throttled(ThrottleRequestsException $e): JsonResponse
    {
        /** @var array<string, string> $headers */
        $headers = $e->getHeaders();
        $retryAfter = (int) ($headers['Retry-After'] ?? 0);

        return ApiResponse::error(
            (string) __('api.errors.too_many_requests', ['seconds' => $retryAfter]),
            429,
            'too_many_requests',
            meta: ['retry_after' => $retryAfter],
            headers: $headers,
        );
    }
}
