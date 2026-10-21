<?php

namespace App\Models;

use App\Casts\GeoPointCast;
use App\Enums\Location\LocationAnomaly;
use App\Enums\Location\LocationSource;
use App\Support\Geo\GeoPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row of the append-only location history. Flagged samples (`anomaly`) are kept for review.
 *
 * @property int $id
 * @property int $provider_profile_id
 * @property GeoPoint $point
 * @property float|null $accuracy_m
 * @property int|null $heading
 * @property float|null $speed_mps
 * @property LocationSource $source
 * @property LocationAnomaly|null $anomaly
 * @property int|null $booking_id
 * @property Carbon $recorded_at
 * @property Carbon $created_at
 */
class ProviderLocation extends Model
{
    public const UPDATED_AT = null;

    /** Millisecond precision: positions of one driver arrive seconds apart. */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['provider_profile_id', 'point', 'accuracy_m', 'heading', 'speed_mps', 'source', 'anomaly', 'booking_id', 'recorded_at'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'point' => GeoPointCast::class,
            'accuracy_m' => 'float',
            'heading' => 'integer',
            'speed_mps' => 'float',
            'source' => LocationSource::class,
            'anomaly' => LocationAnomaly::class,
            'recorded_at' => 'datetime',
        ];
    }
}
