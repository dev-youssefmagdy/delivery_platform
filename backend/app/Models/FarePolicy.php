<?php

namespace App\Models;

use App\Enums\Pricing\FareFormula;
use App\Support\Audit\Auditable;
use App\Support\Money\Currency;
use App\Support\Money\Money;
use Database\Factories\FarePolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reference-price configuration of one service category, optionally for one zone (SRS §9.1). The calculator turns a
 * route and the booking's answers into guidance (low / recommended / high); the policy never forces a fare and has no
 * demand multiplier. Bookings copy the values they were priced with into their own snapshot, so editing or
 * deleting a policy never changes history (Plan I4).
 *
 * @property int $id
 * @property int $service_category_id
 * @property int|null $service_zone_id
 * @property FareFormula $formula_version
 * @property string $currency
 * @property int $base_minor
 * @property int $per_km_minor
 * @property int $per_min_minor
 * @property int $min_fare_minor
 * @property int $stop_fee_minor
 * @property list<array{field: string, mode: string, amount_minor: int, match?: string|null, label?: array<string, string|null>}>|null $attribute_surcharges
 * @property int $rounding_step_minor
 * @property int $range_low_bp
 * @property int $range_high_bp
 * @property bool $active
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property string|null $notes
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ServiceCategory $serviceCategory
 * @property-read ServiceZone|null $serviceZone
 */
class FarePolicy extends Model
{
    use Auditable;

    /** @use HasFactory<FarePolicyFactory> */
    use HasFactory;

    protected $fillable = [
        'service_category_id', 'service_zone_id', 'formula_version', 'currency', 'base_minor', 'per_km_minor', 'per_min_minor',
        'min_fare_minor', 'stop_fee_minor', 'attribute_surcharges', 'rounding_step_minor', 'range_low_bp', 'range_high_bp', 'active',
        'effective_from', 'effective_to', 'notes', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'formula_version' => 1,
        'base_minor' => 0,
        'per_km_minor' => 0,
        'per_min_minor' => 0,
        'min_fare_minor' => 0,
        'stop_fee_minor' => 0,
        'rounding_step_minor' => 100,
        'range_low_bp' => 8500,
        'range_high_bp' => 12500,
        'active' => true,
        'lock_version' => 0,
    ];

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<ServiceZone, $this>
     */
    public function serviceZone(): BelongsTo
    {
        return $this->belongsTo(ServiceZone::class);
    }

    public function isEffectiveAt(Carbon $at): bool
    {
        return $this->active && $this->effective_from->lessThanOrEqualTo($at) && ($this->effective_to === null || $this->effective_to->greaterThan($at));
    }

    /**
     * @param 'base_minor'|'per_km_minor'|'per_min_minor'|'min_fare_minor'|'stop_fee_minor' $column
     */
    public function money(string $column, Currency $currency): Money
    {
        return new Money((int) $this->getAttribute($column), $currency);
    }

    /**
     * The values a booking stores to explain its estimate later, whatever happens to this row.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'id' => $this->id,
            'lock_version' => $this->lock_version,
            'service_category_id' => $this->service_category_id,
            'service_zone_id' => $this->service_zone_id,
            'formula_version' => $this->formula_version->value,
            'currency' => $this->currency,
            'base_minor' => $this->base_minor,
            'per_km_minor' => $this->per_km_minor,
            'per_min_minor' => $this->per_min_minor,
            'min_fare_minor' => $this->min_fare_minor,
            'stop_fee_minor' => $this->stop_fee_minor,
            'attribute_surcharges' => $this->attribute_surcharges ?? [],
            'rounding_step_minor' => $this->rounding_step_minor,
            'range_low_bp' => $this->range_low_bp,
            'range_high_bp' => $this->range_high_bp,
            'effective_from' => $this->effective_from->toIso8601String(),
            'effective_to' => $this->effective_to?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'formula_version' => FareFormula::class,
            'base_minor' => 'integer',
            'per_km_minor' => 'integer',
            'per_min_minor' => 'integer',
            'min_fare_minor' => 'integer',
            'stop_fee_minor' => 'integer',
            'attribute_surcharges' => 'array',
            'rounding_step_minor' => 'integer',
            'range_low_bp' => 'integer',
            'range_high_bp' => 'integer',
            'active' => 'boolean',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
