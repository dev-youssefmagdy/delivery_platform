<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A photo, video or document the customer attached to a moving / freight request. It is uploaded first (booking_id null),
 * then referenced by uuid from the service form (`photos`, `documents`). Files live on the private disk and are never
 * served except through signed routes to the booking's customer and its selected provider.
 *
 * @property int $id
 * @property string $uuid
 * @property int $customer_id
 * @property int|null $booking_id
 * @property string|null $field
 * @property string $kind photo|video|document
 * @property string $disk
 * @property string|null $storage_path
 * @property string $mime
 * @property int $size_bytes
 * @property string $sha256
 * @property string|null $scan_status
 * @property Carbon $created_at
 */
class BookingAttachment extends Model
{
    public const KIND_PHOTO = 'photo';

    public const KIND_VIDEO = 'video';

    public const KIND_DOCUMENT = 'document';

    protected $fillable = ['uuid', 'customer_id', 'booking_id', 'field', 'kind', 'disk', 'storage_path', 'mime', 'size_bytes', 'sha256', 'scan_status'];

    protected static function booted(): void
    {
        static::creating(static function (self $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
        });
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
        return ['size_bytes' => 'integer'];
    }
}
