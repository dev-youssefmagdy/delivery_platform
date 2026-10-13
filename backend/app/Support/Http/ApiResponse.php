<?php

namespace App\Support\Http;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Context;
use stdClass;

/**
 * The only producer of the JSON envelope used by the API and admin AJAX responses (ADR-14):
 * `{ success, message, data, meta, errors }`. Domain error codes go in `errors._code`.
 */
final class ApiResponse
{
    public const CODE_KEY = '_code';

    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $headers
     */
    public static function success(
        ?string $message = null,
        mixed $data = null,
        array $meta = [],
        int $status = 200,
        array $headers = [],
    ): JsonResponse {
        return self::envelope(true, $message ?? __('api.ok'), self::resolveData($data), $meta, [], $status, $headers);
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function created(?string $message = null, mixed $data = null, array $meta = []): JsonResponse
    {
        return self::success($message ?? __('api.created'), $data, $meta, 201);
    }

    /**
     * @param array<string, list<string>|string> $errors field => messages
     * @param array<string, mixed> $meta
     * @param array<string, string> $headers
     */
    public static function error(
        string $message,
        int $status = 400,
        ?string $code = null,
        array $errors = [],
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        $normalized = [];

        foreach ($errors as $field => $messages) {
            $normalized[$field] = is_array($messages) ? $messages : [$messages];
        }

        if ($code !== null) {
            $normalized = [self::CODE_KEY => $code] + $normalized;
        }

        return self::envelope(false, $message, null, $meta, $normalized, $status, $headers);
    }

    /**
     * Paginated listing. `$resource` is a JsonResource class applied to each item.
     *
     * @param LengthAwarePaginator<array-key, mixed>|CursorPaginator<array-key, mixed> $paginator
     * @param class-string<JsonResource>|null $resource
     * @param array<string, mixed> $meta
     */
    public static function paginated(
        LengthAwarePaginator|CursorPaginator $paginator,
        ?string $resource = null,
        ?string $message = null,
        array $meta = [],
    ): JsonResponse {
        $items = $resource !== null
            ? $resource::collection($paginator->items())->resolve()
            : $paginator->items();

        $pagination = $paginator instanceof LengthAwarePaginator
            ? [
                'type' => 'page',
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
            : [
                'type' => 'cursor',
                'per_page' => $paginator->perPage(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ];

        return self::success($message, $items, ['pagination' => $pagination] + $meta);
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, list<string>|string> $errors
     * @param array<string, string> $headers
     */
    private static function envelope(bool $success, string $message, mixed $data, array $meta, array $errors, int $status, array $headers): JsonResponse
    {
        $requestId = Context::get('request_id');

        if (! $success && is_string($requestId)) {
            $meta['request_id'] = $requestId;
        }

        return new JsonResponse([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'meta' => $meta === [] ? new stdClass : $meta,
            'errors' => $errors === [] ? new stdClass : $errors,
        ], $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    private static function resolveData(mixed $data): mixed
    {
        if ($data instanceof ResourceCollection || $data instanceof JsonResource) {
            return $data->resolve(request());
        }

        return $data;
    }
}
