<?php

namespace App\Models;

use App\DTOs\Coupon\CouponTerms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * An immutable snapshot of a coupon's financial terms. Reservations and redemptions point at the version they were made
 * under; editing a live coupon's terms adds a version and never touches an existing one.
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $version
 * @property array<string, mixed> $terms
 * @property string|null $reason
 * @property int|null $created_by_id
 * @property Carbon $created_at
 */
class CouponVersion extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['coupon_id', 'version', 'terms', 'reason', 'created_by_id'];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Coupon versions are immutable: add a new version.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Coupon versions are immutable.');
        });
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function termsObject(): CouponTerms
    {
        return CouponTerms::fromSnapshot($this->terms);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['terms' => 'array', 'version' => 'integer', 'created_at' => 'datetime'];
    }
}
