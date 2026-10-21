<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A time-limited public link to follow one trip (SRS §6.6, §13.1). Only the SHA-256 of the token is stored.
 *
 * @property int $id
 * @property int $booking_id
 * @property string $token_hash
 * @property int $created_by_user_id
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 */
class BookingShareLink extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['booking_id', 'token_hash', 'created_by_user_id', 'expires_at', 'revoked_at'];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isActiveAt(Carbon $at): bool
    {
        return $this->revoked_at === null && $this->expires_at->greaterThan($at);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
