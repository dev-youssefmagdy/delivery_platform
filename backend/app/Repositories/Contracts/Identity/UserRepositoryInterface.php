<?php

namespace App\Repositories\Contracts\Identity;

use App\Enums\Identity\UserStatus;
use App\Models\User;
use App\Repositories\Contracts\BaseRepositoryInterface;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByPhone(string $e164, bool $withTrashed = false): ?User;

    /**
     * Race-safe "find or create" on the unique phone. The second element is true when the row was created.
     *
     * @param array<string, mixed> $attributes set only on creation
     * @return array{0: User, 1: bool}
     */
    public function firstOrCreateByPhone(string $e164, array $attributes): array;

    public function markPhoneVerified(User $user): User;

    public function setStatus(User $user, UserStatus $status): User;

    public function setPhoto(User $user, ?string $path): User;

    /**
     * Refreshes `last_seen_at` unless it is already newer than $interval seconds.
     */
    public function touchLastSeen(User $user, int $interval): void;
}
