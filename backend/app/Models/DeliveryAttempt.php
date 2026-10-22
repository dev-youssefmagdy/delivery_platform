<?php

namespace App\Models;

use App\Enums\Booking\DeliveryAttemptOutcome;
use App\Enums\Booking\DeliveryAttemptResolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A failed hand-over at the drop-off and what was decided afterwards (SRS §8.1, §20.4).
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property int $provider_id
 * @property int $attempt_number
 * @property DeliveryAttemptOutcome $outcome
 * @property string|null $reason
 * @property int|null $evidence_proof_id
 * @property DeliveryAttemptResolution|null $resolution
 * @property string|null $resolved_by_type
 * @property int|null $resolved_by_id
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 */
class DeliveryAttempt extends Model
{
    protected $fillable = [
        'uuid', 'booking_id', 'provider_id', 'attempt_number', 'outcome', 'reason', 'evidence_proof_id',
        'resolution', 'resolved_by_type', 'resolved_by_id', 'resolved_at',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $attempt): void {
            $attempt->uuid ??= (string) Str::uuid();
        });
    }

    public function isPending(): bool
    {
        return $this->resolution === null;
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
            'outcome' => DeliveryAttemptOutcome::class,
            'resolution' => DeliveryAttemptResolution::class,
            'resolved_at' => 'datetime',
        ];
    }
}
