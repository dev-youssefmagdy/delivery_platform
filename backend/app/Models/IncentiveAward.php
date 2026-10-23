<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A quest bonus paid to a provider wallet through the ledger.
 *
 * @property int $id
 * @property string $uuid
 * @property int $progress_id
 * @property int $campaign_id
 * @property int $provider_id
 * @property int $sequence
 * @property int $amount_minor
 * @property string $currency
 * @property int|null $journal_entry_id
 * @property string $idempotency_key
 * @property Carbon $granted_at
 */
class IncentiveAward extends Model
{
    /** @var list<string> */
    protected $fillable = ['uuid', 'progress_id', 'campaign_id', 'provider_id', 'sequence', 'amount_minor', 'currency', 'journal_entry_id', 'idempotency_key', 'granted_at'];

    protected static function booted(): void
    {
        static::creating(static function (self $award): void {
            $award->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'granted_at' => 'datetime'];
    }
}
