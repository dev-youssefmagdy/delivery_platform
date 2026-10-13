<?php

namespace App\Repositories\Contracts\Audit;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Collection;

/**
 * Append-only: there are deliberately no update or delete methods.
 */
interface AuditLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function append(array $attributes): AdminAuditLog;

    /**
     * @return Collection<int, AdminAuditLog>
     */
    public function latestForAuditable(string $auditableType, string $auditableId, int $limit = 50): Collection;
}
