<?php

namespace App\Models;

use App\Enums\Growth\IncentiveProgressStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One provider's progress in one incentive campaign. `held` = a weak fraud flag stops the payout until a person decides.
 *
 * @property int $id
 * @property string $uuid
 * @property int $campaign_id
 * @property int $provider_id
 * @property int $completed_jobs
 * @property int $awards_count
 * @property IncentiveProgressStatus $status
 * @property list<array{flag: string, evidence: array<string, mixed>}>|null $flags
 * @property string|null $decision_reason
 * @property int|null $decided_by_admin_id
 * @property Carbon|null $last_counted_at
 * @property-read IncentiveCampaign $campaign
 * @property-read ProviderProfile $provider
 */
class IncentiveProgress extends Model
{
    use Auditable;

    protected $table = 'incentive_progress';

    /** @var list<string> */
    protected $fillable = ['uuid', 'campaign_id', 'provider_id', 'completed_jobs', 'awards_count', 'status', 'flags', 'decision_reason', 'decided_by_admin_id', 'last_counted_at'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'in_progress', 'completed_jobs' => 0, 'awards_count' => 0];

    protected static function booted(): void
    {
        static::creating(static function (self $progress): void {
            $progress->uuid ??= (string) Str::uuid();
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
        return ['status' => IncentiveProgressStatus::class, 'flags' => 'array', 'last_counted_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<IncentiveCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(IncentiveCampaign::class, 'campaign_id');
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_id');
    }

    /**
     * @return HasMany<IncentiveAward, $this>
     */
    public function awards(): HasMany
    {
        return $this->hasMany(IncentiveAward::class, 'progress_id');
    }
}
