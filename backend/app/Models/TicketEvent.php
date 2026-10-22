<?php

namespace App\Models;

use App\Enums\Common\ActorType;
use App\Enums\Support\TicketEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only history of a ticket (status, assignment, escalation, resolution ...). Not shown to users.
 *
 * @property int $id
 * @property int $ticket_id
 * @property TicketEventType $type
 * @property ActorType $actor_type
 * @property int|null $actor_id
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class TicketEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'type', 'actor_type', 'actor_id', 'meta'];

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Ticket events are append-only.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Ticket events are append-only.');
        });
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['type' => TicketEventType::class, 'actor_type' => ActorType::class, 'meta' => 'array'];
    }
}
