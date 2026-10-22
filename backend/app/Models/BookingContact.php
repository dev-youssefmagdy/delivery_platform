<?php

namespace App\Models;

use App\Enums\Booking\ContactRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Sender or recipient of a parcel. Name and phone are encrypted at rest; plain values exist only through
 * `name()` / `phone()` and are handed to a provider only by the services that apply the staged-disclosure rules.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property ContactRole $role
 * @property string|null $name_encrypted
 * @property string|null $phone_encrypted
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookingContact extends Model
{
    protected $fillable = ['uuid', 'booking_id', 'role', 'name_encrypted', 'phone_encrypted'];

    protected $hidden = ['name_encrypted', 'phone_encrypted'];

    protected static function booted(): void
    {
        static::creating(static function (self $contact): void {
            $contact->uuid ??= (string) Str::uuid();
        });
    }

    public function name(): ?string
    {
        return $this->name_encrypted === null ? null : Crypt::decryptString($this->name_encrypted);
    }

    public function phone(): ?string
    {
        return $this->phone_encrypted === null ? null : Crypt::decryptString($this->phone_encrypted);
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
        return ['role' => ContactRole::class];
    }
}
