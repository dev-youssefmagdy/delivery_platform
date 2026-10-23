<?php

namespace App\Models;

use App\Enums\Safety\TrustedContactRelationship;
use App\Support\Audit\Auditable;
use App\Support\Auth\PhoneNumber;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Somebody a user chose to tell in an emergency or when sharing a trip. The phone is encrypted at rest and only the masked
 * form leaves the server in an API response.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $name
 * @property string $phone_encrypted
 * @property string $phone_hash
 * @property TrustedContactRelationship|null $relationship
 * @property Carbon $consent_confirmed_at
 * @property bool $notify_on_sos
 * @property bool $share_trip
 * @property bool $share_location_on_sos
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TrustedContact extends Model
{
    use Auditable;
    use HasUuids;

    protected $fillable = ['user_id', 'name', 'phone_encrypted', 'phone_hash', 'relationship', 'consent_confirmed_at', 'notify_on_sos', 'share_trip', 'share_location_on_sos'];

    protected $hidden = ['phone_encrypted', 'phone_hash'];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function phone(): string
    {
        return (string) decrypt($this->phone_encrypted);
    }

    public function maskedPhone(): string
    {
        return PhoneNumber::mask($this->phone());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'relationship' => TrustedContactRelationship::class,
            'consent_confirmed_at' => 'datetime',
            'notify_on_sos' => 'boolean',
            'share_trip' => 'boolean',
            'share_location_on_sos' => 'boolean',
        ];
    }
}
