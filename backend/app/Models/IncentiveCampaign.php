<?php

namespace App\Models;

use App\Enums\Growth\CampaignStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider incentive quest (SRS 7.6): complete `target_jobs` qualifying jobs inside the window to earn `bonus_minor`, up to
 * `max_awards_per_provider` times, until the budget is spent. Eligibility: cities, service categories, minimum rating, minimum order.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $name_en
 * @property CampaignStatus $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $currency
 * @property int $target_jobs
 * @property int $bonus_minor
 * @property int $max_awards_per_provider
 * @property int $budget_minor
 * @property int $spent_minor
 * @property int|null $min_order_minor
 * @property string|null $min_rating decimal string
 * @property list<int>|null $service_category_ids
 * @property list<int>|null $city_ids
 * @property int $max_jobs_per_customer
 * @property string|null $status_reason
 * @property int $lock_version
 * @property int|null $created_by_admin_id
 */
class IncentiveCampaign extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = [
        'uuid', 'name', 'name_en', 'status', 'starts_at', 'ends_at', 'currency', 'target_jobs', 'bonus_minor', 'max_awards_per_provider', 'budget_minor',
        'spent_minor', 'min_order_minor', 'min_rating', 'service_category_ids', 'city_ids', 'max_jobs_per_customer', 'status_reason', 'lock_version',
        'created_by_admin_id',
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
            'status' => CampaignStatus::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'service_category_ids' => 'array', 'city_ids' => 'array',
            'bonus_minor' => 'integer', 'budget_minor' => 'integer', 'spent_minor' => 'integer', 'min_order_minor' => 'integer',
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
        return $this->status === CampaignStatus::Active && $this->starts_at->lte($at) && $this->ends_at->gt($at);
    }

    /**
     * @return HasMany<IncentiveProgress, $this>
     */
    public function progress(): HasMany
    {
        return $this->hasMany(IncentiveProgress::class, 'campaign_id');
    }
}
