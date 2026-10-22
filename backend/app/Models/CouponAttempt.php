<?php

namespace App\Models;

use App\Enums\Coupon\CouponAttemptContext;
use App\Enums\Coupon\CouponAttemptResult;
use App\Enums\Coupon\CouponReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One attempt to use a coupon (validation, booking, automatic selection, an admin's test): append-only (SRS §11.2 step 8).
 *
 * @property int $id
 * @property int|null $coupon_id
 * @property string|null $code_entered
 * @property int|null $user_id
 * @property int|null $booking_id
 * @property int|null $service_category_id
 * @property CouponAttemptContext $context
 * @property CouponAttemptResult $result
 * @property string $reason_code
 * @property int|null $discount_minor
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 * @property-read Coupon|null $coupon
 * @property-read User|null $user
 */
class CouponAttempt extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['coupon_id', 'code_entered', 'user_id', 'booking_id', 'service_category_id', 'context', 'result', 'reason_code', 'discount_minor', 'meta'];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Coupon attempts are append-only.');
        });
    }

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

    public function reason(): ?CouponReason
    {
        return CouponReason::tryFrom($this->reason_code);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'context' => CouponAttemptContext::class,
            'result' => CouponAttemptResult::class,
            'discount_minor' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
