<?php

namespace App\Models;

use App\Casts\GeoPointCast;
use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CustodyStatus;
use App\Enums\Catalog\BookingType;
use App\Enums\Payments\PaymentMethodType;
use App\Enums\Payments\PaymentStatus;
use App\Support\Booking\BookingStatusGate;
use App\Support\Geo\GeoPoint;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * A customer's request for a service and, later, its agreed trip (SRS §19.3). Everything the booking was priced and
 * validated with is copied into the snapshots at creation, so admin edits never change it (Plan I4). `status` is
 * written only by BookingTransitionService (ADR-16); totals are never accepted from clients (Plan I12).
 *
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property int $customer_id
 * @property int $service_category_id
 * @property int $service_category_version_id
 * @property int $city_id
 * @property int|null $service_zone_id
 * @property int|null $fare_policy_id
 * @property BookingType $booking_type
 * @property BookingStatus $status
 * @property int $sequence
 * @property int $request_version
 * @property GeoPoint $pickup_point
 * @property array<string, mixed>|null $pickup_address
 * @property GeoPoint|null $dropoff_point
 * @property array<string, mixed>|null $dropoff_address
 * @property Carbon|null $scheduled_at
 * @property int|null $passengers
 * @property int|null $luggage_count
 * @property string|null $cargo_weight_kg decimal string
 * @property string|null $cargo_volume_m3 decimal string
 * @property array<string, mixed>|null $requested_scope
 * @property Carbon|null $pickup_window_start
 * @property Carbon|null $pickup_window_end
 * @property array<string, mixed> $service_details
 * @property int $proposed_fare_minor
 * @property string $currency
 * @property Money|null $proposed_fare
 * @property array<string, mixed>|null $reference_estimate
 * @property array<string, mixed>|null $route_snapshot
 * @property array<string, mixed> $service_snapshot
 * @property array<string, mixed> $policy_snapshot
 * @property array<string, mixed>|null $commission_snapshot
 * @property int|null $commission_rule_id
 * @property int|null $commission_rule_version_id
 * @property array<string, mixed>|null $price_breakdown
 * @property Carbon|null $settled_at
 * @property PaymentMethodType $payment_method
 * @property PaymentStatus $payment_status
 * @property int|null $coupon_reservation_id
 * @property int|null $selected_offer_id
 * @property int|null $agreed_fare_minor
 * @property int|null $agreed_offer_version_id
 * @property array<string, mixed>|null $agreed_scope
 * @property Carbon|null $provider_selected_at
 * @property Carbon|null $heading_at
 * @property Carbon|null $arrived_at
 * @property array<string, mixed>|null $arrival_evidence
 * @property Carbon|null $free_wait_ends_at
 * @property Carbon|null $started_at
 * @property int|null $final_fare_minor
 * @property int $adjustments_total_minor
 * @property int|null $cancellation_fee_entry_id
 * @property Carbon|null $cancellation_fee_posted_at
 * @property int|null $cancellation_policy_id
 * @property string|null $cancellation_reason_code
 * @property int|null $cancellation_fee_minor
 * @property array<string, mixed>|null $cancellation_decision
 * @property int $pin_attempts
 * @property Carbon|null $pin_verified_at
 * @property Carbon|null $pin_locked_at
 * @property Carbon|null $support_flagged_at
 * @property string|null $support_flag_reason
 * @property int|null $restricted_goods_policy_version_id
 * @property Carbon|null $restricted_goods_acknowledged_at
 * @property CustodyStatus|null $custody_status
 * @property int $delivery_attempts_count
 * @property string|null $parcel_size
 * @property string|null $parcel_category
 * @property bool|null $is_fragile
 * @property int|null $declared_value_minor
 * @property int|null $provider_id
 * @property Carbon|null $search_expires_at
 * @property string|null $pin_hash
 * @property string|null $pin_encrypted
 * @property Carbon|null $cancelled_at
 * @property string|null $cancelled_by_type
 * @property int|null $cancelled_by_id
 * @property string|null $cancellation_reason
 * @property Carbon|null $completed_at
 * @property string|null $idempotency_key
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $customer
 * @property-read ServiceCategory $serviceCategory
 * @property-read ServiceCategoryVersion $serviceCategoryVersion
 * @property-read City $city
 * @property-read ServiceZone|null $serviceZone
 * @property-read ProviderProfile|null $provider
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingStop> $stops
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingStatusEvent> $statusEvents
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * `status` is deliberately not fillable: see BookingStatusGate.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'code', 'customer_id', 'service_category_id', 'service_category_version_id', 'city_id', 'service_zone_id', 'fare_policy_id',
        'booking_type', 'sequence', 'request_version', 'pickup_point', 'pickup_address', 'dropoff_point', 'dropoff_address', 'scheduled_at',
        'passengers', 'luggage_count', 'cargo_weight_kg', 'cargo_volume_m3', 'requested_scope', 'pickup_window_start', 'pickup_window_end', 'service_details', 'proposed_fare', 'reference_estimate', 'route_snapshot',
        'service_snapshot', 'policy_snapshot', 'commission_snapshot', 'commission_rule_id', 'commission_rule_version_id', 'price_breakdown', 'settled_at', 'payment_method', 'payment_status', 'coupon_reservation_id',
        'selected_offer_id', 'agreed_fare_minor', 'agreed_offer_version_id', 'agreed_scope', 'provider_selected_at', 'provider_id', 'search_expires_at', 'pin_hash', 'pin_encrypted', 'cancelled_at', 'cancelled_by_type',
        'cancelled_by_id', 'cancellation_reason', 'completed_at', 'idempotency_key', 'lock_version',
        'heading_at', 'arrived_at', 'arrival_evidence', 'free_wait_ends_at', 'started_at', 'final_fare_minor', 'cancellation_policy_id',
        'cancellation_reason_code', 'cancellation_fee_minor', 'cancellation_decision', 'pin_attempts', 'pin_verified_at', 'pin_locked_at',
        'support_flagged_at', 'support_flag_reason', 'adjustments_total_minor', 'cancellation_fee_entry_id', 'cancellation_fee_posted_at',
        'restricted_goods_policy_version_id', 'restricted_goods_acknowledged_at', 'custody_status', 'delivery_attempts_count',
        'parcel_size', 'parcel_category', 'is_fragile', 'declared_value_minor',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['sequence' => 0, 'request_version' => 1, 'lock_version' => 0, 'service_details' => '[]', 'pin_attempts' => 0, 'adjustments_total_minor' => 0, 'delivery_attempts_count' => 0];

    /**
     * @var list<string>
     */
    protected $hidden = ['pin_hash', 'pin_encrypted'];

    protected static function booted(): void
    {
        static::creating(static function (self $booking): void {
            $booking->uuid ??= (string) Str::uuid();
        });

        static::saving(static function (self $booking): void {
            if ($booking->exists && $booking->isDirty('status') && ! BookingStatusGate::isOpen()) {
                throw new LogicException('bookings.status is written only by BookingTransitionService.');
            }

            // The accepted scope is part of the agreement: it is written once at selection and cleared when the agreement ends
            // (provider cancelled, back to search). A later price change goes through an adjustment request, never through the scope.
            if ($booking->exists && $booking->isDirty('agreed_scope') && $booking->getOriginal('agreed_scope') !== null && $booking->agreed_scope !== null) {
                throw new LogicException('bookings.agreed_scope is immutable once the offer is accepted.');
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<ServiceCategoryVersion, $this>
     */
    public function serviceCategoryVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceCategoryVersion::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<ServiceZone, $this>
     */
    public function serviceZone(): BelongsTo
    {
        return $this->belongsTo(ServiceZone::class);
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    /**
     * @return HasMany<BookingOffer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(BookingOffer::class);
    }

    /**
     * @return HasMany<BookingStop, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(BookingStop::class)->orderBy('position');
    }

    /**
     * @return HasMany<BookingContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(BookingContact::class);
    }

    /**
     * @return HasMany<BookingStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(BookingStatusEvent::class)->orderBy('sequence');
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
            'booking_type' => BookingType::class,
            'status' => BookingStatus::class,
            'custody_status' => CustodyStatus::class,
            'is_fragile' => 'boolean',
            'restricted_goods_acknowledged_at' => 'datetime',
            'sequence' => 'integer',
            'request_version' => 'integer',
            'pickup_point' => GeoPointCast::class,
            'pickup_address' => 'array',
            'dropoff_point' => GeoPointCast::class,
            'dropoff_address' => 'array',
            'scheduled_at' => 'datetime',
            'passengers' => 'integer',
            'luggage_count' => 'integer',
            'service_details' => 'array',
            'proposed_fare' => MoneyCast::class.':proposed_fare_minor,currency',
            'proposed_fare_minor' => 'integer',
            'reference_estimate' => 'array',
            'route_snapshot' => 'array',
            'service_snapshot' => 'array',
            'policy_snapshot' => 'array',
            'commission_snapshot' => 'array',
            'commission_rule_id' => 'integer',
            'commission_rule_version_id' => 'integer',
            'price_breakdown' => 'array',
            'settled_at' => 'datetime',
            'payment_method' => PaymentMethodType::class,
            'payment_status' => PaymentStatus::class,
            'agreed_fare_minor' => 'integer',
            'agreed_scope' => 'array',
            'requested_scope' => 'array',
            'pickup_window_start' => 'datetime',
            'pickup_window_end' => 'datetime',
            'provider_selected_at' => 'datetime',
            'heading_at' => 'datetime',
            'arrived_at' => 'datetime',
            'arrival_evidence' => 'array',
            'free_wait_ends_at' => 'datetime',
            'started_at' => 'datetime',
            'final_fare_minor' => 'integer',
            'adjustments_total_minor' => 'integer',
            'cancellation_fee_entry_id' => 'integer',
            'cancellation_fee_posted_at' => 'datetime',
            'cancellation_fee_minor' => 'integer',
            'cancellation_decision' => 'array',
            'pin_attempts' => 'integer',
            'pin_verified_at' => 'datetime',
            'pin_locked_at' => 'datetime',
            'support_flagged_at' => 'datetime',
            'search_expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
