<?php

namespace App\Models;

use App\Casts\GeoPolygonCast;
use App\Enums\Geo\ZoneStatus;
use App\Support\Audit\Auditable;
use App\Support\Geo\GeoPolygon;
use App\Support\Geo\OperatingHours;
use Database\Factories\ServiceZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A drawn service area of a city (SRS §16.5). The polygon decides where bookings can start; drawing a polygon
 * is not by itself legal authorization to operate there (the admin hint says so). Category availability lives
 * in the `zone_service_category` pivot.
 *
 * @property int $id
 * @property int $city_id
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property GeoPolygon $polygon
 * @property ZoneStatus $status
 * @property list<array{day: int, from: string, to: string}>|null $operating_hours null = always open
 * @property bool $restricted_pickup
 * @property string|null $notes
 * @property bool $is_demo
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read City $city
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ZoneServiceCategory> $categoryLinks
 */
class ServiceZone extends Model
{
    use Auditable;

    /** @use HasFactory<ServiceZoneFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['city_id', 'name', 'polygon', 'status', 'operating_hours', 'restricted_pickup', 'notes', 'is_demo', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'restricted_pickup' => false,
        'is_demo' => false,
        'lock_version' => 0,
    ];

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<ZoneServiceCategory, $this>
     */
    public function categoryLinks(): HasMany
    {
        return $this->hasMany(ZoneServiceCategory::class, 'service_zone_id');
    }

    public function hours(): OperatingHours
    {
        return OperatingHours::fromArray($this->operating_hours);
    }

    /**
     * The polygon is summarized (vertex count + fingerprint) so audit rows stay small.
     *
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        $snapshot = array_diff_key($this->attributesToArray(), array_flip($this->auditExcludedAttributes()));
        $snapshot['polygon'] = ['vertices' => count($this->polygon->vertices()), 'fingerprint' => $this->polygon->fingerprint()];

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'polygon' => GeoPolygonCast::class,
            'status' => ZoneStatus::class,
            'operating_hours' => 'array',
            'restricted_pickup' => 'boolean',
            'is_demo' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
