<?php

namespace App\Services\Audit;

use App\Enums\Common\ActorType;
use App\Enums\Common\AuditAction;
use App\Models\AdminAuditLog;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

/**
 * Writes append-only audit entries (Plan §4.4). Request metadata (request id, IP, user agent) comes from
 * Laravel Context populated by the CorrelationId middleware, so services never read the HTTP request and
 * queued jobs keep the originating request id.
 */
final class AuditLogger
{
    public function __construct(private readonly AuditLogRepositoryInterface $logs) {}

    /**
     * @param Model|string $target a model, or a string subject such as "settings" for non-model actions
     * @param array<string, mixed>|null $before use $model->auditSnapshot(); never pass secrets
     * @param array<string, mixed>|null $after
     * @param array<string, mixed> $meta
     */
    public function record(
        AuditAction $action,
        Model|string $target,
        ?Authenticatable $actor = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        array $meta = [],
    ): AdminAuditLog {
        [$auditableType, $auditableId] = $target instanceof Model
            ? [$target->getMorphClass(), $target->getKey() === null ? null : (string) $target->getKey()]
            : [$target, null];

        [$actorType, $actorId] = $this->actor($actor);
        $ip = Context::getHidden('ip');
        $userAgent = Context::getHidden('user_agent');
        $requestId = Context::get('request_id');

        return $this->logs->append([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip' => is_string($ip) ? $ip : null,
            'user_agent' => is_string($userAgent) ? Str::limit($userAgent, 512, '') : null,
            'request_id' => is_string($requestId) ? $requestId : null,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    /**
     * Convenience for updates: stores only the attributes that changed.
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, mixed> $meta
     */
    public function recordChanges(
        AuditAction $action,
        Model $target,
        ?Authenticatable $actor,
        array $before,
        array $after,
        ?string $reason = null,
        array $meta = [],
    ): AdminAuditLog {
        $changedKeys = array_keys(array_filter(
            $after,
            static fn (mixed $value, string $key): bool => ! array_key_exists($key, $before) || $before[$key] !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));
        $changedKeys = array_values(array_unique(array_merge($changedKeys, array_keys(array_diff_key($before, $after)))));

        return $this->record(
            $action,
            $target,
            $actor,
            array_intersect_key($before, array_flip($changedKeys)),
            array_intersect_key($after, array_flip($changedKeys)),
            $reason,
            $meta,
        );
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function actor(?Authenticatable $actor): array
    {
        if ($actor === null) {
            return [ActorType::System->value, null];
        }

        $type = $actor instanceof Model ? $actor->getMorphClass() : $actor::class;
        $id = $actor->getAuthIdentifier();

        return [$type, is_numeric($id) ? (int) $id : null];
    }
}
