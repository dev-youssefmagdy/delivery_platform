<?php

namespace App\Models;

use App\Enums\Provider\VehiclePhotoKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A photo of a vehicle on the private disk (re-encoded JPEG, metadata stripped).
 *
 * @property int $id
 * @property string $uuid
 * @property int $vehicle_id
 * @property VehiclePhotoKind $kind
 * @property string $path
 * @property string $mime
 * @property int $size_bytes
 * @property string $file_hash
 * @property Carbon $created_at
 * @property-read Vehicle $vehicle
 */
class VehiclePhoto extends Model
{
    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected $hidden = ['path'];

    protected static function booted(): void
    {
        static::creating(static function (self $photo): void {
            $photo->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => VehiclePhotoKind::class, 'size_bytes' => 'integer'];
    }
}
