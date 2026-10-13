<?php

namespace App\Repositories\Contracts\Common;

use App\Models\IdempotencyKey;
use Carbon\CarbonInterface;

interface IdempotencyKeyRepositoryInterface
{
    public function findActive(string $actorType, int $actorId, string $route, string $key): ?IdempotencyKey;

    /**
     * Insert or overwrite (an expired row with the same scope is replaced).
     */
    public function store(
        string $actorType,
        int $actorId,
        string $route,
        string $key,
        string $requestHash,
        int $status,
        string $body,
        CarbonInterface $expiresAt,
    ): IdempotencyKey;
}
