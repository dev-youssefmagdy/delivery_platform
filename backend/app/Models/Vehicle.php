<?php

namespace App\Models;

use App\Enums\Provider\VehicleStatus;
use App\Support\Audit\Auditable;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider's vehicle (SRS §19.2). `plate_normalized` is the unique, comparison form of the plate (latin digits,
 * no spaces or dashes, upper case); `plate_display` is what the provider typed.
 *
 * @property int $id
 * @property string $uuid
 * @property int $provider_profile_id
 * @property int $vehicle_category_id
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string $color
 * @property string $plate_display
 * @property string $plate_normalized
 * @property int|null $passenger_capacity
 * @property string|null $payload_kg decimal string
 * @property string|null $volume_m3 decimal string
 * @property list<string> $capabilities VehicleCapability values
 * @property VehicleStatus $status
 * @property string|null $rejection_reason
 * @property Carbon|null $status_changed_at
 * @property Carbon|null $submitted_at
 * @property int|null $reviewed_by_admin_id
 * @property Carbon|null $reviewed_at
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ProviderProfile $providerProfile
 * @property-read VehicleCategory $vehicleCategory
 */
class Vehicle extends Model
{
    use Auditable;

    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'provider_profile_id', 'vehicle_category_id', 'make', 'model', 'year', 'color', 'plate_display', 'plate_normalized',
        'passenger_capacity', 'payload_kg', 'volume_m3', 'capabilities', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'capabilities' => '[]',
        'lock_version' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $vehicle): void {
            $vehicle->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * @return BelongsTo<VehicleCategory, $this>
     */
    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    /**
     * @return HasMany<VehiclePhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class);
    }

    /**
     * @return HasMany<VehicleDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VehicleStatus::class,
            'year' => 'integer',
            'passenger_capacity' => 'integer',
            'payload_kg' => 'decimal:2',
            'volume_m3' => 'decimal:2',
            'capabilities' => 'array',
            'status_changed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
