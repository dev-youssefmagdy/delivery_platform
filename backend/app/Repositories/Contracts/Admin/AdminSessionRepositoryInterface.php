<?php

namespace App\Repositories\Contracts\Admin;

use App\Models\AdminSession;
use Illuminate\Database\Eloquent\Collection;

interface AdminSessionRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): AdminSession;

    public function findBySessionId(string $sessionId): ?AdminSession;

    public function find(int $id): ?AdminSession;

    /**
     * @return Collection<int, AdminSession>
     */
    public function forAdmin(int $adminId): Collection;

    public function touch(AdminSession $session): void;

    public function delete(AdminSession $session): void;

    /**
     * Deletes every session row of the admin (optionally keeping one) and returns the removed session ids.
     *
     * @return list<string>
     */
    public function deleteForAdmin(int $adminId, ?string $exceptSessionId = null): array;
}
