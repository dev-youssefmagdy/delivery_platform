<?php

namespace App\Models;

use App\Casts\GeoPointCast;
use App\Support\Geo\GeoPoint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * An intermediate stop of a booking, in order (position 1 is the first stop after pickup).
 *
 * @property int $id
 * @property int $booking_id
 * @property int $position
 * @property GeoPoint $point
 * @property array<string, mixed>|null $address
 * @property string|null $contact_encrypted
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookingStop extends Model
{
    protected $fillable = ['booking_id', 'position', 'point', 'address', 'contact_encrypted', 'metadata'];

    protected $hidden = ['contact_encrypted'];

    /**
     * The person to call at this stop (clear text only through the services that apply staged disclosure).
     *
     * @return array{name: string, phone: string, note?: string}|null
     */
    public function contact(): ?array
    {
        if ($this->contact_encrypted === null) {
            return null;
        }

        /** @var array{name: string, phone: string, note?: string} */
        return json_decode(Crypt::decryptString($this->contact_encrypted), true);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'point' => GeoPointCast::class,
            'address' => 'array',
            'metadata' => 'array',
        ];
    }
}
