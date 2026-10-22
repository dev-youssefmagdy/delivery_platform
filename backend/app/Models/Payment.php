<?php

namespace App\Models;

use App\Enums\Payments\PaymentGatewayName;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Support\Audit\Auditable;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One charge of a booking: the trip fare or a tip (SRS §12.2). `status` changes only through PaymentTransitionService,
 * which enforces the PaymentStatus graph and appends a `payment_events` row for each change.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property int $user_id
 * @property int|null $provider_id
 * @property PaymentKind $kind
 * @property PaymentMethodType $method_type
 * @property int|null $payment_method_id
 * @property PaymentGatewayName|null $gateway
 * @property int $amount_minor
 * @property string $currency
 * @property Money|null $amount
 * @property PaymentStatus $status
 * @property string|null $gateway_reference
 * @property string|null $failure_code
 * @property int $refunded_minor
 * @property int $attempt
 * @property Carbon|null $expires_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $last_event_at
 * @property Carbon|null $settled_at
 * @property int|null $settlement_entry_id
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Booking $booking
 * @property-read User $user
 * @property-read PaymentMethod|null $paymentMethod
 */
class Payment extends Model
{
    use Auditable;

    /**
     * `status` is deliberately not fillable: only PaymentTransitionService writes it.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'booking_id', 'user_id', 'provider_id', 'kind', 'method_type', 'payment_method_id', 'gateway', 'amount_minor', 'currency',
        'gateway_reference', 'failure_code', 'refunded_minor', 'attempt', 'expires_at', 'paid_at', 'last_event_at', 'settled_at',
        'settlement_entry_id', 'meta',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['refunded_minor' => 0, 'attempt' => 1];

    protected static function booted(): void
    {
        static::creating(static function (self $payment): void {
            $payment->uuid ??= (string) Str::uuid();
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class)->withTrashed();
    }

    /**
     * @return HasMany<PaymentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderBy('id');
    }

    /**
     * @return HasMany<PaymentRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class)->orderByDesc('id');
    }

    /**
     * What may still be refunded: the collected amount minus completed and pending refunds (the caller subtracts the pending ones).
     */
    public function refundableMinor(): int
    {
        return max(0, $this->amount_minor - $this->refunded_minor);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'kind' => PaymentKind::class,
            'method_type' => PaymentMethodType::class,
            'gateway' => PaymentGatewayName::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'refunded_minor' => 'integer',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_event_at' => 'datetime',
            'settled_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
