<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * The receipt of a completed booking, issued once at completion and never edited (Plan I4): the snapshot holds the fare
 * lines, approved adjustments, discount, fees, tax, parties and policy as they were then, so later changes of prices,
 * commission rules, policies or names cannot alter what the customer was shown.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property string $number
 * @property string $kind
 * @property string $currency
 * @property int $total_minor
 * @property array<string, mixed> $snapshot
 * @property Carbon $issued_at
 * @property Carbon $created_at
 * @property-read Booking $booking
 */
class BookingReceipt extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['uuid', 'booking_id', 'number', 'kind', 'currency', 'total_minor', 'snapshot', 'issued_at'];

    protected static function booted(): void
    {
        static::creating(static function (self $receipt): void {
            $receipt->uuid ??= (string) Str::uuid();
        });

        static::updating(static function (): void {
            throw new LogicException('Receipts are immutable.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Receipts are immutable.');
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
            'total_minor' => 'integer',
            'snapshot' => 'array',
            'issued_at' => 'datetime',
        ];
    }
}
