<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Database\Factories\VehicleCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A vehicle class (tuk-tuk, economy car, 2-ton truck ...) with its capacities and map marker. SRS §5.1, §16.4.
 *
 * @property int $id
 * @property string $code
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string|null $description translated in the current locale; raw JSON {ar, en}
 * @property string|null $icon
 * @property string|null $map_marker
 * @property int|null $passenger_capacity
 * @property int|null $luggage_capacity
 * @property string|null $max_payload_kg decimal string
 * @property string|null $max_volume_m3 decimal string
 * @property int $sort_order
 * @property bool $active
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class VehicleCategory extends Model
{
    use Auditable;

    /** @use HasFactory<VehicleCategoryFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'code', 'name', 'description', 'icon', 'map_marker', 'passenger_capacity', 'luggage_capacity',
        'max_payload_kg', 'max_volume_m3', 'sort_order', 'active', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'active' => false,
        'lock_version' => 0,
    ];

    /**
     * @return BelongsToMany<ServiceCategory, $this>
     */
    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'category_vehicle_category', 'vehicle_category_id', 'service_category_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passenger_capacity' => 'integer',
            'luggage_capacity' => 'integer',
            'max_payload_kg' => 'decimal:2',
            'max_volume_m3' => 'decimal:2',
            'sort_order' => 'integer',
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
