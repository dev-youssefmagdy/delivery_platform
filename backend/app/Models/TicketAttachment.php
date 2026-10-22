<?php

namespace App\Models;

use App\Enums\Provider\FileScanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidence attached to a ticket message; the file lives on the private disk and is served through signed routes only.
 *
 * @property int $id
 * @property string $uuid
 * @property int $ticket_id
 * @property int|null $ticket_message_id
 * @property string $path
 * @property string|null $original_name
 * @property string $mime
 * @property int $size_bytes
 * @property string $file_hash
 * @property FileScanStatus $scan_status
 * @property-read TicketMessage|null $message
 * @property-read SupportTicket $ticket
 */
class TicketAttachment extends Model
{
    use HasUuids;

    protected $fillable = ['ticket_id', 'ticket_message_id', 'path', 'original_name', 'mime', 'size_bytes', 'file_hash', 'scan_status'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<SupportTicket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * @return BelongsTo<TicketMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['scan_status' => FileScanStatus::class];
    }
}
