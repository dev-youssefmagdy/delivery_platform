<?php

namespace App\Services\Audit;

use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Read side of the audit log for admin "Activity" tabs/timelines.
 */
final class AuditTrailService
{
    public function __construct(private readonly AuditLogRepositoryInterface $logs) {}

    /**
     * @return Collection<int, AdminAuditLog>
     */
    public function latestFor(Model $model, int $limit = 50): Collection
    {
        return $this->logs->latestForAuditable($model->getMorphClass(), (string) $model->getKey(), $limit);
    }
}
