<?php

namespace App\Models;

use App\Enums\Safety\TrustedContactActionStatus;
use App\Enums\Safety\TrustedContactActionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * What the platform did (or did not do) towards one trusted contact, with the reason. Only the masked phone is stored.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $trusted_contact_id
 * @property int|null $incident_id
 * @property int|null $booking_id
 * @property TrustedContactActionType $action
 * @property string $channel
 * @property TrustedContactActionStatus $status
 * @property string|null $reason
 * @property string|null $contact_name
 * @property string|null $contact_phone_masked
 * @property bool $included_location
 * @property bool $included_trip_link
 * @property string|null $gateway
 * @property Carbon|null $trip_link_expires_at
 * @property Carbon $created_at
 */
class TrustedContactAction extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'trusted_contact_id', 'incident_id', 'booking_id', 'action', 'channel', 'status', 'reason', 'contact_name',
        'contact_phone_masked', 'included_location', 'included_trip_link', 'gateway', 'trip_link_expires_at',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return BelongsTo<SafetyIncident, $this>
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class, 'incident_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'action' => TrustedContactActionType::class,
            'status' => TrustedContactActionStatus::class,
            'included_location' => 'boolean',
            'included_trip_link' => 'boolean',
            'trip_link_expires_at' => 'datetime',
        ];
    }
}
