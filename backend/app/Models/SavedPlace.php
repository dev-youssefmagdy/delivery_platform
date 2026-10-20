<?php

namespace App\Models;

use App\Casts\GeoPointCast;
use App\Enums\Geo\SavedPlaceType;
use App\Support\Geo\GeoPoint;
use Database\Factories\SavedPlaceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A customer's saved place (Home / Work / other). `address` is a snapshot of the geocoder answer taken when it
 * was saved; it is shown as a hint and never used to decide anything.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $label
 * @property SavedPlaceType $type
 * @property GeoPoint $point
 * @property array{title?: string|null, line?: string|null, precision?: string|null, provider?: string|null}|null $address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SavedPlace extends Model
{
    /** @use HasFactory<SavedPlaceFactory> */
    use HasFactory;

    use HasUuids;

    protected $fillable = ['user_id', 'label', 'type', 'point', 'address'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => SavedPlaceType::class,
            'point' => GeoPointCast::class,
            'address' => 'array',
        ];
    }
}
