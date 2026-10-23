<?php

namespace App\Models;

use App\Enums\Growth\ReferralAudience;
use App\Enums\Growth\RelationshipStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Who referred whom. `fraud_flags` holds weak signals ({flag, evidence}) that put the relationship under review; they never punish.
 *
 * @property int $id
 * @property string $uuid
 * @property int $campaign_id
 * @property int $referral_code_id
 * @property ReferralAudience $audience
 * @property int $referrer_user_id
 * @property int $referee_user_id
 * @property RelationshipStatus $status
 * @property int $jobs_completed
 * @property list<array{flag: string, evidence: array<string, mixed>}>|null $fraud_flags
 * @property Carbon|null $qualified_at
 * @property Carbon|null $decided_at
 * @property int|null $decided_by_admin_id
 * @property string|null $decision_reason
 * @property int $lock_version
 * @property Carbon $created_at
 * @property-read ReferralCampaign $campaign
 * @property-read User $referrer
 * @property-read User $referee
 */
class ReferralRelationship extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = [
        'uuid', 'campaign_id', 'referral_code_id', 'audience', 'referrer_user_id', 'referee_user_id', 'status', 'jobs_completed', 'fraud_flags',
        'qualified_at', 'decided_at', 'decided_by_admin_id', 'decision_reason', 'lock_version',
    ];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending', 'jobs_completed' => 0, 'lock_version' => 0];

    protected static function booted(): void
    {
        static::creating(static function (self $relationship): void {
            $relationship->uuid ??= (string) Str::uuid();
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
        return ['audience' => ReferralAudience::class, 'status' => RelationshipStatus::class, 'fraud_flags' => 'array', 'qualified_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<ReferralCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ReferralCampaign::class, 'campaign_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    /**
     * @return HasMany<ReferralReward, $this>
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class, 'relationship_id');
    }

    /**
     * @return list<string> flag keys
     */
    public function flagKeys(): array
    {
        return array_map(static fn (array $flag): string => $flag['flag'], $this->fraud_flags ?? []);
    }
}
