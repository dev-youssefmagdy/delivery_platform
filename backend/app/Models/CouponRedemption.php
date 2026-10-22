<?php

namespace App\Models;

use App\Enums\Coupon\CouponRedemptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A committed use of a coupon on a completed booking: the auditable record of the discount and who funded it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $coupon_id
 * @property int $coupon_version_id
 * @property int $reservation_id
 * @property int $user_id
 * @property int $booking_id
 * @property CouponRedemptionStatus $status
 * @property string $currency
 * @property int $fare_minor
 * @property int $discount_minor
 * @property int $platform_minor
 * @property int $provider_minor
 * @property int $partner_minor
 * @property Carbon $redeemed_at
 * @property Carbon|null $reversed_at
 * @property string|null $reversal_reason
 * @property-read Coupon $coupon
 * @property-read User $user
 * @property-read Booking $booking
 */
class CouponRedemption extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'coupon_id', 'coupon_version_id', 'reservation_id', 'user_id', 'booking_id', 'status', 'currency', 'fare_minor',
        'discount_minor', 'platform_minor', 'provider_minor', 'partner_minor', 'redeemed_at', 'reversed_at', 'reversal_reason',
    ];

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
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
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => CouponRedemptionStatus::class,
            'fare_minor' => 'integer',
            'discount_minor' => 'integer',
            'platform_minor' => 'integer',
            'provider_minor' => 'integer',
            'partner_minor' => 'integer',
            'redeemed_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
