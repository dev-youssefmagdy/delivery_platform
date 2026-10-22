<?php

namespace App\Models;

use App\Enums\Payments\PaymentEventType;
use App\Enums\Payments\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * A row of a payment's timeline. Append-only: it is never edited or deleted.
 *
 * @property int $id
 * @property int $payment_id
 * @property PaymentEventType $type
 * @property PaymentStatus|null $from_status
 * @property PaymentStatus|null $to_status
 * @property string $source webhook|query|system|admin|provider|api
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $gateway_event_id
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class PaymentEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['payment_id', 'type', 'from_status', 'to_status', 'source', 'actor_type', 'actor_id', 'gateway_event_id', 'meta'];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Payment events are never edited.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Payment events are never deleted.');
        });
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentEventType::class,
            'from_status' => PaymentStatus::class,
            'to_status' => PaymentStatus::class,
            'meta' => 'array',
        ];
    }
}
