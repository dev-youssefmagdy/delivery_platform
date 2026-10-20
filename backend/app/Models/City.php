<?php

namespace App\Models;

use App\Casts\GeoPointCast;
use App\Support\Audit\Auditable;
use App\Support\Geo\GeoPoint;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A city of a market (SRS §16.5). Its timezone drives operating hours and displayed times; the center is where
 * maps open. A city serves bookings only through its active service zones.
 *
 * @property int $id
 * @property int $country_id
 * @property string $name translated in the current locale; raw JSON {ar, en}
 * @property string $timezone
 * @property GeoPoint $center
 * @property bool $active
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Country $country
 */
class City extends Model
{
    use Auditable;

    /** @use HasFactory<CityFactory> */
    use HasFactory;

    use HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['country_id', 'name', 'timezone', 'center', 'active', 'lock_version'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => false,
        'lock_version' => 0,
    ];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return HasMany<ServiceZone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(ServiceZone::class, 'city_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function auditSnapshot(): array
    {
        $snapshot = array_diff_key($this->attributesToArray(), array_flip($this->auditExcludedAttributes()));
        $snapshot['center'] = $this->center->toArray();

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'center' => GeoPointCast::class,
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }
}
