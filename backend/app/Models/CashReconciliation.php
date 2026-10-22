<?php

namespace App\Models;

use App\Enums\Finance\CashReconciliationStatus;
use App\Enums\Finance\CashSettlementMethod;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider's cash commission settlement for a period: the cash the provider collected and the commission it owes (snapshot
 * of the period's completed cash jobs), and the money received when settled (posted to the ledger as a new entry).
 *
 * @property int $id
 * @property string $uuid
 * @property int $provider_profile_id
 * @property string $currency
 * @property Carbon $period_from
 * @property Carbon $period_to
 * @property int $cash_jobs
 * @property int $cash_collected_minor
 * @property int $commission_due_minor
 * @property int $settled_minor
 * @property CashReconciliationStatus $status
 * @property CashSettlementMethod|null $method
 * @property string|null $reference
 * @property string|null $notes
 * @property int|null $journal_entry_id
 * @property int $created_by_id
 * @property int|null $settled_by_id
 * @property Carbon|null $settled_at
 * @property string $idempotency_key
 * @property Carbon $created_at
 * @property-read ProviderProfile $provider
 * @property-read Admin|null $createdBy
 * @property-read Admin|null $settledBy
 */
class CashReconciliation extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'provider_profile_id', 'currency', 'period_from', 'period_to', 'cash_jobs', 'cash_collected_minor', 'commission_due_minor',
        'settled_minor', 'status', 'method', 'reference', 'notes', 'journal_entry_id', 'created_by_id', 'settled_by_id', 'settled_at', 'idempotency_key',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $reconciliation): void {
            $reconciliation->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_profile_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'settled_by_id');
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
            'period_from' => 'date',
            'period_to' => 'date',
            'cash_jobs' => 'integer',
            'cash_collected_minor' => 'integer',
            'commission_due_minor' => 'integer',
            'settled_minor' => 'integer',
            'status' => CashReconciliationStatus::class,
            'method' => CashSettlementMethod::class,
            'settled_at' => 'datetime',
        ];
    }
}
