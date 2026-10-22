<?php

namespace App\Models;

use App\Enums\Coupon\CouponScopeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One scope row of a coupon (a service family, category, vehicle category, city or zone it is limited to).
 *
 * @property int $id
 * @property int $coupon_id
 * @property CouponScopeType $type
 * @property int $ref_id
 */
class CouponScope extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = ['coupon_id', 'type', 'ref_id'];

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['type' => CouponScopeType::class, 'ref_id' => 'integer'];
    }
}
