<?php

namespace App\Models;

use App\Enums\Dispatch\DispatchResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A provider that was notified of a request, with the wave and distance (SRS §10.2). Providers only ever see requests
 * they were dispatched; `booking_id` references `bookings` once P2.3 creates it.
 *
 * @property int $id
 * @property int $booking_id
 * @property int $provider_profile_id
 * @property int $wave
 * @property int $distance_m
 * @property Carbon $notified_at
 * @property Carbon|null $seen_at
 * @property DispatchResponse $response
 * @property Carbon|null $responded_at
 * @property-read ProviderProfile $providerProfile
 */
class DispatchRecipient extends Model
{
    protected $table = 'booking_dispatch_recipients';

    protected $fillable = ['booking_id', 'provider_profile_id', 'wave', 'distance_m', 'notified_at', 'seen_at', 'response', 'responded_at'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['response' => 'pending'];

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
            'wave' => 'integer',
            'distance_m' => 'integer',
            'notified_at' => 'datetime',
            'seen_at' => 'datetime',
            'response' => DispatchResponse::class,
            'responded_at' => 'datetime',
        ];
    }
}
