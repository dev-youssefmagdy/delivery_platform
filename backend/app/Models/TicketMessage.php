<?php

namespace App\Models;

use App\Enums\Common\ActorType;
use App\Enums\Support\TicketVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One message of a ticket thread. `internal` messages are staff-only notes and are never returned by the user API.
 *
 * @property int $id
 * @property string $uuid
 * @property int $ticket_id
 * @property TicketVisibility $visibility
 * @property ActorType $author_type
 * @property int|null $author_user_id
 * @property int|null $author_admin_id
 * @property int|null $macro_id
 * @property string $body
 * @property Carbon $created_at
 * @property-read Admin|null $authorAdmin
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketAttachment> $attachments
 */
class TicketMessage extends Model
{
    use HasUuids;

    protected $fillable = ['ticket_id', 'visibility', 'author_type', 'author_user_id', 'author_admin_id', 'macro_id', 'body'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function isInternal(): bool
    {
        return $this->visibility === TicketVisibility::Internal;
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function authorAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'author_admin_id')->withTrashed();
    }

    /**
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_message_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['visibility' => TicketVisibility::class, 'author_type' => ActorType::class];
    }
}
