<?php

namespace App\Repositories\Eloquent\Common;

use App\Models\IdempotencyKey;
use App\Repositories\Contracts\Common\IdempotencyKeyRepositoryInterface;
use Carbon\CarbonInterface;

final class IdempotencyKeyRepository implements IdempotencyKeyRepositoryInterface
{
    public function __construct(private readonly IdempotencyKey $model) {}

    public function findActive(string $actorType, int $actorId, string $route, string $key): ?IdempotencyKey
    {
        return $this->model->newQuery()
            ->where('actor_type', $actorType)
            ->where('actor_id', $actorId)
            ->where('route', $route)
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function store(
        string $actorType,
        int $actorId,
        string $route,
        string $key,
        string $requestHash,
        int $status,
        string $body,
        CarbonInterface $expiresAt,
    ): IdempotencyKey {
        return $this->model->newQuery()->updateOrCreate(
            ['actor_type' => $actorType, 'actor_id' => $actorId, 'route' => $route, 'key' => $key],
            ['request_hash' => $requestHash, 'response_status' => $status, 'response_body' => $body, 'expires_at' => $expiresAt],
        );
    }
}
