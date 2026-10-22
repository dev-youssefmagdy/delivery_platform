<?php

namespace App\Models;

use App\Enums\Coupon\CouponReservationStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A coupon use held for a booking (ADR-17): reserved at creation, released on cancel / expiry, committed on completion,
 * reversed on a refund when the coupon's policy says so.
 *
 * @property int $id
 * @property string $uuid
 * @property int $coupon_id
 * @property int $coupon_version_id
 * @property int $user_id
 * @property int|null $booking_id
 * @property CouponReservationStatus $status
 * @property int $hold_minor
 * @property string $currency
 * @property string|null $released_reason
 * @property Carbon $reserved_at
 * @property Carbon|null $committed_at
 * @property Carbon|null $released_at
 * @property Carbon|null $reversed_at
 * @property-read Coupon $coupon
 * @property-read CouponVersion $version
 * @property-read CouponRedemption|null $redemption
 */
class CouponReservation extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'coupon_id', 'coupon_version_id', 'user_id', 'booking_id', 'status', 'hold_minor', 'currency', 'released_reason',
        'reserved_at', 'committed_at', 'released_at', 'reversed_at',
    ];

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return BelongsTo<CouponVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(CouponVersion::class, 'coupon_version_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return HasOne<CouponRedemption, $this>
     */
    public function redemption(): HasOne
    {
        return $this->hasOne(CouponRedemption::class, 'reservation_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => CouponReservationStatus::class,
            'hold_minor' => 'integer',
            'reserved_at' => 'datetime',
            'committed_at' => 'datetime',
            'released_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
