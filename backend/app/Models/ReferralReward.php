<?php

namespace App\Models;

use App\Enums\Growth\CreditStatus;
use App\Enums\Growth\RewardRecipient;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A reward credit posted to a wallet through the ledger (`journal_entry_id`), with an expiry. After `expires_at` the unspent part is
 * taken back by a compensating entry (`expiry_entry_id`, `expired_minor`).
 *
 * @property int $id
 * @property string $uuid
 * @property int $relationship_id
 * @property int $campaign_id
 * @property int $user_id
 * @property RewardRecipient $recipient
 * @property string $wallet_type
 * @property int $amount_minor
 * @property int $expired_minor
 * @property string $currency
 * @property CreditStatus $status
 * @property Carbon $granted_at
 * @property Carbon|null $expires_at
 * @property int|null $journal_entry_id
 * @property int|null $expiry_entry_id
 * @property string $idempotency_key
 * @property-read ReferralRelationship $relationship
 * @property-read ReferralCampaign $campaign
 * @property-read User $user
 */
class ReferralReward extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = [
        'uuid', 'relationship_id', 'campaign_id', 'user_id', 'recipient', 'wallet_type', 'amount_minor', 'expired_minor', 'currency', 'status',
        'granted_at', 'expires_at', 'journal_entry_id', 'expiry_entry_id', 'idempotency_key',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $reward): void {
            $reward->uuid ??= (string) Str::uuid();
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
        return ['recipient' => RewardRecipient::class, 'status' => CreditStatus::class, 'amount_minor' => 'integer', 'expired_minor' => 'integer', 'granted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<ReferralRelationship, $this>
     */
    public function relationship(): BelongsTo
    {
        return $this->belongsTo(ReferralRelationship::class, 'relationship_id');
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
