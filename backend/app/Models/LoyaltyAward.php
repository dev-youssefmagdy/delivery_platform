<?php

namespace App\Models;

use App\Enums\Growth\CreditStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The one-time credit granted when a customer reaches a loyalty level (ledger entry + expiry), once per user and level.
 *
 * @property int $id
 * @property int $user_id
 * @property int $level_id
 * @property int $amount_minor
 * @property int $expired_minor
 * @property string $currency
 * @property CreditStatus $status
 * @property Carbon $granted_at
 * @property Carbon|null $expires_at
 * @property int|null $journal_entry_id
 * @property int|null $expiry_entry_id
 * @property string $idempotency_key
 * @property-read LoyaltyLevel $level
 */
class LoyaltyAward extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'level_id', 'amount_minor', 'expired_minor', 'currency', 'status', 'granted_at', 'expires_at', 'journal_entry_id', 'expiry_entry_id', 'idempotency_key'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['status' => CreditStatus::class, 'amount_minor' => 'integer', 'expired_minor' => 'integer', 'granted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<LoyaltyLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(LoyaltyLevel::class, 'level_id');
    }
}
