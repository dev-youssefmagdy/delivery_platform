<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Where, when and for whom a commission applies (SRS §12.4): service, zone, provider tier and campaign scope, a
 * period, a priority and an enabled flag. The numbers live in immutable versions; `current_version_id` points at the
 * one new bookings use.
 *
 * @property int $id
 * @property string $name
 * @property int|null $service_category_id
 * @property int|null $service_zone_id
 * @property string|null $provider_tier
 * @property string|null $campaign_code
 * @property string $currency
 * @property int $priority
 * @property bool $active
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property int|null $current_version_id
 * @property string|null $notes
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ServiceCategory|null $serviceCategory
 * @property-read ServiceZone|null $serviceZone
 * @property-read CommissionRuleVersion|null $currentVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CommissionRuleVersion> $versions
 */
class CommissionRule extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name', 'service_category_id', 'service_zone_id', 'provider_tier', 'campaign_code', 'currency', 'priority', 'active',
        'effective_from', 'effective_to', 'current_version_id', 'notes', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['priority' => 0, 'active' => true, 'lock_version' => 0];

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

    /**
     * @return BelongsTo<CommissionRuleVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(CommissionRuleVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<CommissionRuleVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(CommissionRuleVersion::class)->orderByDesc('version');
    }

    public function isEffectiveAt(Carbon $at): bool
    {
        return $this->active && $this->effective_from->lessThanOrEqualTo($at) && ($this->effective_to === null || $this->effective_to->greaterThan($at));
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'service_category_id' => 'integer',
            'service_zone_id' => 'integer',
            'priority' => 'integer',
            'active' => 'boolean',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'current_version_id' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
