<?php

namespace App\Models;

use App\Enums\Coupon\CouponStatus;
use App\Enums\Coupon\DiscountType;
use App\Enums\Coupon\FundingSource;
use App\Support\Audit\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * A promotion (SRS §11.1). `code` is null for an automatic campaign. The financial terms are frozen per `version` in
 * `coupon_versions`; counters (`used_count`, `reserved_count`, `spent_minor`, `reserved_minor`) change only under the row lock
 * of CouponReservationService.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $code
 * @property string $name
 * @property string $currency
 * @property DiscountType $discount_type
 * @property int $discount_value basis points (percentage) or minor units (fixed)
 * @property int|null $max_discount_minor
 * @property int|null $min_fare_minor
 * @property int|null $per_customer_limit
 * @property int|null $global_limit
 * @property int|null $budget_minor
 * @property int $spent_minor
 * @property int $reserved_minor
 * @property int $used_count
 * @property int $reserved_count
 * @property FundingSource $funding_source
 * @property array<string, int>|null $funding_split
 * @property bool $stackable
 * @property bool $restore_on_refund
 * @property array<string, list<int>>|null $exclusions
 * @property list<string>|null $segments
 * @property list<string>|null $payment_methods
 * @property list<array{day: int, from: string, to: string}>|null $schedule
 * @property CouponStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $timezone
 * @property int $priority
 * @property int $version
 * @property int $lock_version
 * @property int|null $created_by_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponScope> $scopes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CouponVersion> $versions
 */
class Coupon extends Model
{
    use Auditable;
    use HasTranslations;

    /**
     * @var list<string>
     */
    public array $translatable = ['title', 'description', 'legal_terms'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'code', 'name', 'title', 'description', 'legal_terms', 'currency', 'discount_type', 'discount_value', 'max_discount_minor',
        'min_fare_minor', 'per_customer_limit', 'global_limit', 'budget_minor', 'spent_minor', 'reserved_minor', 'used_count', 'reserved_count',
        'funding_source', 'funding_split', 'stackable', 'restore_on_refund', 'exclusions', 'segments', 'payment_methods', 'schedule', 'status',
        'starts_at', 'ends_at', 'timezone', 'priority', 'version', 'lock_version', 'created_by_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'spent_minor' => 0, 'reserved_minor' => 0, 'used_count' => 0, 'reserved_count' => 0, 'stackable' => false,
        'restore_on_refund' => true, 'priority' => 0, 'version' => 1, 'lock_version' => 0,
    ];

    /**
     * @var list<string>
     */
    protected array $auditExcluded = ['created_at', 'updated_at'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return HasMany<CouponScope, $this>
     */
    public function scopes(): HasMany
    {
        return $this->hasMany(CouponScope::class);
    }

    /**
     * @return HasMany<CouponVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CouponVersion::class)->orderByDesc('version');
    }

    public function isAutomatic(): bool
    {
        return $this->code === null;
    }

    public function isWithinWindow(CarbonInterface $at): bool
    {
        return ($this->starts_at === null || $this->starts_at->lessThanOrEqualTo($at))
            && ($this->ends_at === null || $this->ends_at->greaterThan($at));
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'funding_source' => FundingSource::class,
            'status' => CouponStatus::class,
            'funding_split' => 'array',
            'exclusions' => 'array',
            'segments' => 'array',
            'payment_methods' => 'array',
            'schedule' => 'array',
            'stackable' => 'boolean',
            'restore_on_refund' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'discount_value' => 'integer',
            'max_discount_minor' => 'integer',
            'min_fare_minor' => 'integer',
            'per_customer_limit' => 'integer',
            'global_limit' => 'integer',
            'budget_minor' => 'integer',
            'spent_minor' => 'integer',
            'reserved_minor' => 'integer',
            'used_count' => 'integer',
            'reserved_count' => 'integer',
            'priority' => 'integer',
            'version' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
