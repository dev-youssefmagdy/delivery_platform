<?php

namespace App\Http\Middleware;

use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Repositories\Contracts\Common\IdempotencyKeyRepositoryInterface;
use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * `idempotent` route middleware (Plan §4.4, invariant I9).
 *
 * - Requires an `Idempotency-Key` header (UUID) on unsafe methods.
 * - Scope = (actor, route, key). The first successful (2xx) response is stored for 24h and replayed verbatim
 *   with `Idempotent-Replayed: true`. Non-2xx responses are not stored, so the client may retry.
 * - Same key with a different payload → 422 `idempotency_key_reused`.
 * - Same key while the first request is still running → 409 `idempotency_request_in_progress` (Redis lock).
 *
 * Runs after authentication (registered in the middleware priority list) so the actor is known.
 */
final class Idempotency
{
    public const HEADER = 'Idempotency-Key';

    public const REPLAY_HEADER = 'Idempotent-Replayed';

    public function __construct(
        private readonly IdempotencyKeyRepositoryInterface $keys,
        private readonly CacheFactory $cache,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $key = (string) $request->headers->get(self::HEADER, '');

        if ($key === '') {
            throw BusinessRuleViolation::make('idempotency_key_required', self::HEADER);
        }

        if (! Str::isUuid($key)) {
            throw BusinessRuleViolation::make('idempotency_key_invalid', self::HEADER);
        }

        [$actorType, $actorId] = $this->actor($request);
        $route = $this->routeIdentifier($request);
        $hash = $this->requestHash($request);

        $store = $this->cache->store(config('idempotency.lock_store'))->getStore();

        if (! $store instanceof LockProvider) {
            throw new RuntimeException('The idempotency lock store must support atomic locks.');
        }

        $lock = $store->lock('idempotency:'.hash('sha256', "{$actorType}|{$actorId}|{$route}|{$key}"), (int) config('idempotency.lock_seconds', 30));

        if (! $lock->get()) {
            throw ConflictException::make('idempotency_request_in_progress', self::HEADER);
        }

        try {
            $existing = $this->keys->findActive($actorType, $actorId, $route, $key);

            if ($existing !== null) {
                if (! hash_equals($existing->request_hash, $hash)) {
                    throw BusinessRuleViolation::make('idempotency_key_reused', self::HEADER);
                }

                return response($existing->response_body, $existing->response_status, [
                    'Content-Type' => 'application/json',
                    self::REPLAY_HEADER => 'true',
                ]);
            }

            $response = $next($request);

            if ($response->isSuccessful() && $response->getContent() !== false) {
                $this->keys->store(
                    $actorType,
                    $actorId,
                    $route,
                    $key,
                    $hash,
                    $response->getStatusCode(),
                    $response->getContent(),
                    now()->addHours((int) config('idempotency.ttl_hours', 24)),
                );
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function actor(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return ['guest', 0];
        }

        $id = $user->getAuthIdentifier();

        return [$user->getMorphClass(), is_numeric($id) ? (int) $id : 0];
    }

    private function routeIdentifier(Request $request): string
    {
        $route = $request->route();
        $name = is_object($route) ? ($route->getName() ?? $route->uri()) : $request->path();

        return Str::limit($request->method().' '.$name, 191, '');
    }

    /**
     * Canonical hash of method + path + input (keys sorted recursively) + uploaded file contents.
     */
    private function requestHash(Request $request): string
    {
        $input = $request->except(array_keys($request->allFiles()));
        $this->sortRecursive($input);

        $files = [];

        foreach ($request->allFiles() as $field => $file) {
            foreach ((array) $file as $index => $uploaded) {
                if ($uploaded instanceof UploadedFile) {
                    $files["{$field}.{$index}"] = hash_file('sha256', $uploaded->getRealPath() ?: '') ?: '';
                }
            }
        }
        ksort($files);

        return hash('sha256', (string) json_encode([$request->method(), $request->path(), $input, $files]));
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function sortRecursive(array &$value): void
    {
        ksort($value);

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sortRecursive($item);
            }
        }
    }
}
