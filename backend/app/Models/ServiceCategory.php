<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Database\Factories\ServiceCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * The bookable service (economy car, courier motorcycle, freight truck ...). The row holds the editable draft
 * configuration; publishing writes an immutable ServiceCategoryVersion that clients and bookings use (ADR-65).
 * Null policy fields fall back to the platform settings through CategoryPolicyResolver.
 *
 * @property int $id
 * @property int $service_family_id
 * @property string $code
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string|null $description translated in the current locale; raw JSON {ar, en}
 * @property string|null $icon
 * @property string|null $map_marker
 * @property int $sort_order
 * @property bool $active
 * @property list<string> $allowed_booking_types BookingType values
 * @property bool $negotiation_enabled
 * @property bool $reference_estimate_enabled
 * @property int|null $min_customer_fare_minor
 * @property int|null $max_customer_fare_minor
 * @property int|null $counter_offer_min_pct
 * @property int|null $counter_offer_max_pct
 * @property int|null $offer_ttl_seconds
 * @property int|null $search_ttl_seconds
 * @property list<float|int>|null $dispatch_radius_steps_km
 * @property int|null $dispatch_batch_size
 * @property int|null $max_passengers
 * @property int|null $max_luggage
 * @property string|null $max_weight_kg decimal string
 * @property string|null $max_volume_m3 decimal string
 * @property bool $supports_stops
 * @property int $max_stops
 * @property bool|null $pin_required
 * @property bool $pickup_proof_required
 * @property list<string> $delivery_proof_types ProofType values
 * @property bool $recipient_otp_required
 * @property bool $tipping_enabled
 * @property bool $coupon_eligible
 * @property bool $cash_allowed
 * @property list<string> $payment_methods PaymentMethodType values
 * @property list<array{day: int, from: string, to: string}>|null $service_hours
 * @property int|null $published_version_id
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ServiceFamily $family
 * @property-read ServiceCategoryVersion|null $publishedVersion
 */
class ServiceCategory extends Model
{
    use Auditable;

    /** @use HasFactory<ServiceCategoryFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'service_family_id', 'code', 'name', 'description', 'icon', 'map_marker', 'sort_order', 'active', 'allowed_booking_types',
        'negotiation_enabled', 'reference_estimate_enabled', 'min_customer_fare_minor', 'max_customer_fare_minor',
        'counter_offer_min_pct', 'counter_offer_max_pct', 'offer_ttl_seconds', 'search_ttl_seconds', 'dispatch_radius_steps_km',
        'dispatch_batch_size', 'max_passengers', 'max_luggage', 'max_weight_kg', 'max_volume_m3', 'supports_stops', 'max_stops',
        'pin_required', 'pickup_proof_required', 'delivery_proof_types', 'recipient_otp_required', 'tipping_enabled',
        'coupon_eligible', 'cash_allowed', 'payment_methods', 'service_hours', 'published_version_id', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'active' => false,
        'allowed_booking_types' => '[]',
        'negotiation_enabled' => true,
        'reference_estimate_enabled' => true,
        'supports_stops' => false,
        'max_stops' => 0,
        'pickup_proof_required' => false,
        'delivery_proof_types' => '[]',
        'recipient_otp_required' => false,
        'tipping_enabled' => true,
        'coupon_eligible' => true,
        'cash_allowed' => true,
        'payment_methods' => '["cash"]',
        'lock_version' => 0,
    ];

    /**
     * @return BelongsTo<ServiceFamily, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(ServiceFamily::class, 'service_family_id');
    }

    /**
     * @return BelongsToMany<VehicleCategory, $this>
     */
    public function vehicleCategories(): BelongsToMany
    {
        return $this->belongsToMany(VehicleCategory::class, 'category_vehicle_category', 'service_category_id', 'vehicle_category_id');
    }

    /**
     * @return HasMany<ServiceFormSchema, $this>
     */
    public function formSchemas(): HasMany
    {
        return $this->hasMany(ServiceFormSchema::class, 'service_category_id');
    }

    /**
     * @return HasOne<ServiceFormSchema, $this>
     */
    public function draftSchema(): HasOne
    {
        return $this->hasOne(ServiceFormSchema::class, 'service_category_id')->where('status', 'draft');
    }

    /**
     * @return HasMany<ServiceCategoryVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ServiceCategoryVersion::class, 'service_category_id');
    }

    /**
     * @return BelongsTo<ServiceCategoryVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceCategoryVersion::class, 'published_version_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'active' => 'boolean',
            'allowed_booking_types' => 'array',
            'negotiation_enabled' => 'boolean',
            'reference_estimate_enabled' => 'boolean',
            'min_customer_fare_minor' => 'integer',
            'max_customer_fare_minor' => 'integer',
            'counter_offer_min_pct' => 'integer',
            'counter_offer_max_pct' => 'integer',
            'offer_ttl_seconds' => 'integer',
            'search_ttl_seconds' => 'integer',
            'dispatch_radius_steps_km' => 'array',
            'dispatch_batch_size' => 'integer',
            'max_passengers' => 'integer',
            'max_luggage' => 'integer',
            'max_weight_kg' => 'decimal:2',
            'max_volume_m3' => 'decimal:2',
            'supports_stops' => 'boolean',
            'max_stops' => 'integer',
            'pin_required' => 'boolean',
            'pickup_proof_required' => 'boolean',
            'delivery_proof_types' => 'array',
            'recipient_otp_required' => 'boolean',
            'tipping_enabled' => 'boolean',
            'coupon_eligible' => 'boolean',
            'cash_allowed' => 'boolean',
            'payment_methods' => 'array',
            'service_hours' => 'array',
            'lock_version' => 'integer',
        ];
    }
}
