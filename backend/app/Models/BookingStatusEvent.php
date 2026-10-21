<?php

namespace App\Models;

use App\Enums\Booking\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One entry of a booking's append-only history. `from_status` is null for the creation entry and equal to
 * `to_status` for annotations that are not status changes (for example "request changed").
 *
 * @property int $id
 * @property int $booking_id
 * @property BookingStatus|null $from_status
 * @property BookingStatus $to_status
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property int $sequence
 * @property Carbon $created_at
 */
class BookingStatusEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['booking_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'reason', 'metadata', 'sequence'];

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Booking status events are append-only.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Booking status events are append-only.');
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
        return [
            'from_status' => BookingStatus::class,
            'to_status' => BookingStatus::class,
            'metadata' => 'array',
            'sequence' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
