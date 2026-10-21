<?php

namespace App\Models;

use App\Casts\GeoPointCast;
use App\Enums\Location\LocationSource;
use App\Support\Geo\GeoPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The newest trusted position of a provider (one row each); the freshness of `recorded_at` is what eligibility checks.
 *
 * @property int $id
 * @property int $provider_profile_id
 * @property int|null $city_id
 * @property GeoPoint $point
 * @property float|null $accuracy_m
 * @property int|null $heading
 * @property float|null $speed_mps
 * @property LocationSource $source
 * @property Carbon $recorded_at
 * @property Carbon $received_at
 * @property-read ProviderProfile $providerProfile
 */
class ProviderLastLocation extends Model
{
    /** Millisecond precision: positions of one driver arrive seconds apart. */
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['provider_profile_id', 'city_id', 'point', 'accuracy_m', 'heading', 'speed_mps', 'source', 'recorded_at', 'received_at'];

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

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
            'recorded_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }
}
