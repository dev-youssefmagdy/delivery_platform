<?php

namespace App\Models;

use App\Enums\Common\AuditAction;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only audit entry. Updates and deletes are refused at the model level.
 *
 * @property int $id
 * @property string $actor_type
 * @property int|null $actor_id
 * @property AuditAction $action
 * @property string $auditable_type
 * @property string|null $auditable_id
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string|null $reason
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $request_id
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 */
class AdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_type', 'actor_id', 'action', 'auditable_type', 'auditable_id', 'before', 'after',
        'reason', 'ip', 'user_agent', 'request_id', 'meta',
    ];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Audit log entries are append-only.'));
        static::deleting(static fn (): never => throw new LogicException('Audit log entries are append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'actor_id' => 'integer',
            'before' => 'array',
            'after' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
