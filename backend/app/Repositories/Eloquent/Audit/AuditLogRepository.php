<?php

namespace App\Repositories\Eloquent\Audit;

use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function __construct(private readonly AdminAuditLog $model) {}

    public function append(array $attributes): AdminAuditLog
    {
        return $this->model->newQuery()->create($attributes);
    }

    public function latestForAuditable(string $auditableType, string $auditableId, int $limit = 50): Collection
    {
        return $this->model->newQuery()
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
