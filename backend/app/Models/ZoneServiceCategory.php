<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Availability of one service category in one zone, with optional per-zone overrides:
 * `min_fare_minor`, `max_fare_minor`, `dispatch_radius_steps_km` (list of km), `hours` ({from, to} daily window).
 *
 * @property int $id
 * @property int $service_zone_id
 * @property int $service_category_id
 * @property bool $active
 * @property array{min_fare_minor?: int|null, max_fare_minor?: int|null, dispatch_radius_steps_km?: list<float|int>|null, hours?: array{from: string, to: string}|null}|null $overrides
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ServiceZone $zone
 * @property-read ServiceCategory $category
 */
class ZoneServiceCategory extends Model
{
    protected $table = 'zone_service_category';

    protected $fillable = ['service_zone_id', 'service_category_id', 'active', 'overrides'];

    /**
     * @return BelongsTo<ServiceZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ServiceZone::class, 'service_zone_id');
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'overrides' => 'array',
        ];
    }
}
