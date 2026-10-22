<?php

namespace App\Models;

use App\Enums\Common\ActorType;
use App\Enums\Provider\FileScanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One chat message of a booking between its customer and its selected provider (SRS §14.2). Text and/or one image kept on the
 * private disk. `read_at` is the other side's read receipt. Purged messages keep the row (for the audit trail) without content.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property ActorType $sender_type
 * @property int $sender_user_id
 * @property string|null $body
 * @property string|null $attachment_path
 * @property string|null $attachment_mime
 * @property int|null $attachment_size
 * @property string|null $attachment_hash
 * @property FileScanStatus|null $attachment_scan_status
 * @property Carbon|null $read_at
 * @property Carbon|null $purged_at
 * @property Carbon $created_at
 * @property-read Booking $booking
 */
class BookingMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id', 'sender_type', 'sender_user_id', 'body', 'attachment_path', 'attachment_mime', 'attachment_size',
        'attachment_hash', 'attachment_scan_status', 'read_at', 'purged_at',
    ];

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

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null && $this->purged_at === null;
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'sender_type' => ActorType::class,
            'attachment_scan_status' => FileScanStatus::class,
            'read_at' => 'datetime',
            'purged_at' => 'datetime',
        ];
    }
}
