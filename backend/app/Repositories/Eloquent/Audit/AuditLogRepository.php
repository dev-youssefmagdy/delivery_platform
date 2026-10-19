<?php

namespace App\Repositories\Eloquent\Audit;

use App\DTOs\Admin\AuditLogFilterData;
use App\Enums\Common\AuditAction;
use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function datatableQuery(AuditLogFilterData $filters): Builder
    {
        return $this->model->newQuery()
            ->with('actorAdmin:id,name')
            ->when($filters->actorId, static fn (Builder $query, int $id) => $query->where('actor_type', 'admin')->where('actor_id', $id))
            ->when($filters->actions !== [], static fn (Builder $query) => $query->whereIn(
                'action',
                array_map(static fn (AuditAction $action): string => $action->value, $filters->actions),
            ))
            ->when($filters->targetType, static fn (Builder $query, string $type) => $query->where('auditable_type', $type))
            ->when($filters->targetId, static fn (Builder $query, string $id) => $query->where('auditable_id', $id))
            ->when($filters->from, static fn (Builder $query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($filters->to, static fn (Builder $query, $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->when($filters->hasReason !== null, static fn (Builder $query) => $filters->hasReason
                ? $query->whereNotNull('reason')->where('reason', '!=', '')
                : $query->where(static fn (Builder $inner) => $inner->whereNull('reason')->orWhere('reason', '')));
    }

    public function findWithActor(int $id): ?AdminAuditLog
    {
        return $this->model->newQuery()->with('actorAdmin:id,name,email')->find($id);
    }

    public function targetTypes(): array
    {
        /** @var list<string> $types */
        $types = $this->model->newQuery()->distinct()->orderBy('auditable_type')->pluck('auditable_type')->all();

        return $types;
    }
}
