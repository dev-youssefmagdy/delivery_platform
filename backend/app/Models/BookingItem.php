<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One line of a moving / freight load (SRS §5.1-D, §5.1-E): a checklist item chosen from the service form, a free-text
 * item or a cargo line, with quantity and optional dimensions, volume and weight. Rewritten as a whole whenever the request is.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property string $kind checklist|free|cargo
 * @property string|null $code
 * @property string|null $label
 * @property int $quantity
 * @property string|null $length_cm
 * @property string|null $width_cm
 * @property string|null $height_cm
 * @property string|null $volume_m3
 * @property string|null $weight_kg
 * @property string|null $note
 * @property int $position
 * @property Carbon $created_at
 */
class BookingItem extends Model
{
    protected $fillable = ['uuid', 'booking_id', 'kind', 'code', 'label', 'quantity', 'length_cm', 'width_cm', 'height_cm', 'volume_m3', 'weight_kg', 'note', 'position'];

    protected static function booted(): void
    {
        static::creating(static function (self $item): void {
            $item->uuid ??= (string) Str::uuid();
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
        return ['quantity' => 'integer', 'position' => 'integer'];
    }
}
