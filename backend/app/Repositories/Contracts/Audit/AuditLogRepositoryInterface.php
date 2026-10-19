<?php

namespace App\Repositories\Contracts\Audit;

use App\DTOs\Admin\AuditLogFilterData;
use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Listing query (audit log screen) with the filters card applied; the acting admin is eager loaded.
     *
     * @return Builder<AdminAuditLog>
     */
    public function datatableQuery(AuditLogFilterData $filters): Builder;

    public function findWithActor(int $id): ?AdminAuditLog;

    /**
     * Distinct auditable_type aliases present in the log (target type filter options).
     *
     * @return list<string>
     */
    public function targetTypes(): array;
}
