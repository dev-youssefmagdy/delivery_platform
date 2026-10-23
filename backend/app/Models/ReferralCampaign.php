<?php

namespace App\Models;

use App\Enums\Growth\CampaignStatus;
use App\Enums\Growth\ReferralAudience;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A referral campaign (SRS 11.3): audience, window, reward for the referrer and the referee, milestone, per-referrer cap and budget.
 * Only one active campaign per audience is used for new referrals (the newest one in its window).
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $name_en
 * @property ReferralAudience $audience
 * @property CampaignStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $currency
 * @property int $referrer_reward_minor
 * @property int $referee_reward_minor
 * @property int $milestone_jobs
 * @property int|null $min_order_minor
 * @property list<int>|null $service_category_ids
 * @property list<int>|null $city_ids
 * @property int $credit_valid_days
 * @property int $per_referrer_cap
 * @property int $budget_minor
 * @property int $spent_minor
 * @property string|null $status_reason
 * @property int $lock_version
 * @property int|null $created_by_admin_id
 */
class ReferralCampaign extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = [
        'uuid', 'name', 'name_en', 'audience', 'status', 'starts_at', 'ends_at', 'currency', 'referrer_reward_minor', 'referee_reward_minor',
        'milestone_jobs', 'min_order_minor', 'service_category_ids', 'city_ids', 'credit_valid_days', 'per_referrer_cap', 'budget_minor',
        'spent_minor', 'status_reason', 'lock_version', 'created_by_admin_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'draft', 'lock_version' => 0, 'spent_minor' => 0];

    protected static function booted(): void
    {
        static::creating(static function (self $campaign): void {
            $campaign->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'audience' => ReferralAudience::class, 'status' => CampaignStatus::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime',
            'service_category_ids' => 'array', 'city_ids' => 'array', 'referrer_reward_minor' => 'integer', 'referee_reward_minor' => 'integer',
            'budget_minor' => 'integer', 'spent_minor' => 'integer', 'min_order_minor' => 'integer',
        ];
    }

    public function displayName(): string
    {
        return app()->getLocale() === 'en' && $this->name_en !== null && $this->name_en !== '' ? $this->name_en : $this->name;
    }

    public function remainingBudgetMinor(): int
    {
        return max(0, $this->budget_minor - $this->spent_minor);
    }

    public function isOpenAt(Carbon $at): bool
    {
        return $this->status === CampaignStatus::Active
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->ends_at === null || $this->ends_at->gt($at));
    }

    /**
     * @return HasMany<ReferralRelationship, $this>
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(ReferralRelationship::class, 'campaign_id');
    }
}
