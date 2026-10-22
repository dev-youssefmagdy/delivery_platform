<?php

namespace App\Models;

use App\Enums\Booking\BookingAdjustmentStatus;
use App\Enums\Booking\BookingAdjustmentType;
use App\Enums\Common\ActorType;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A change of the fare after a provider was selected (SRS §9.4): extra stop, waiting, toll / parking at cost or a scope
 * change. One party asks, the OTHER party approves in the app; only approved requests enter the price (the booking
 * keeps their sum in `adjustments_total_minor`). Money is integer minor units; `amount_minor` is the signed change.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property BookingAdjustmentType $type
 * @property BookingAdjustmentStatus $status
 * @property string $requester_type
 * @property int $requester_id
 * @property string $currency
 * @property int $old_total_minor
 * @property int $new_total_minor
 * @property int $amount_minor
 * @property string $reason
 * @property array<string, mixed>|null $evidence
 * @property array<string, mixed>|null $details
 * @property Carbon $expires_at
 * @property string|null $decided_by_type
 * @property int|null $decided_by_id
 * @property Carbon|null $decided_at
 * @property string|null $decision_note
 * @property string|null $idempotency_key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Booking $booking
 */
class BookingAdjustmentRequest extends Model
{
    use Auditable;

    protected $fillable = [
        'uuid', 'booking_id', 'type', 'status', 'requester_type', 'requester_id', 'currency', 'old_total_minor', 'new_total_minor',
        'amount_minor', 'reason', 'evidence', 'details', 'expires_at', 'decided_by_type', 'decided_by_id', 'decided_at', 'decision_note',
        'idempotency_key',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $request): void {
            $request->uuid ??= (string) Str::uuid();
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
     * The party that must answer: the one that did not ask.
     */
    public function counterpartType(): ActorType
    {
        return $this->requester_type === ActorType::Customer->value ? ActorType::Provider : ActorType::Customer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => BookingAdjustmentType::class,
            'status' => BookingAdjustmentStatus::class,
            'old_total_minor' => 'integer',
            'new_total_minor' => 'integer',
            'amount_minor' => 'integer',
            'evidence' => 'array',
            'details' => 'array',
            'expires_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }
}
