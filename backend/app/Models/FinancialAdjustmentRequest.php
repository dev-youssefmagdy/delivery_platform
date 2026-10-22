<?php

namespace App\Models;

use App\Enums\Finance\AdjustmentDirection;
use App\Enums\Finance\ApprovalStatus;
use App\Support\Audit\Auditable;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A manual change of a customer or provider wallet, asked by one admin and decided by another (maker-checker). Approval posts
 * a new ledger entry; nothing existing is edited.
 *
 * @property int $id
 * @property string $uuid
 * @property int $ledger_account_id
 * @property AdjustmentDirection $direction
 * @property int $amount_minor
 * @property Money|null $amount
 * @property string $currency
 * @property ApprovalStatus $status
 * @property string $reason
 * @property string|null $evidence_path
 * @property string|null $reference
 * @property int $requested_by_id
 * @property int|null $decided_by_id
 * @property Carbon|null $decided_at
 * @property string|null $decision_reason
 * @property int|null $journal_entry_id
 * @property string $idempotency_key
 * @property Carbon $created_at
 * @property-read LedgerAccount $account
 * @property-read Admin|null $requestedBy
 * @property-read Admin|null $decidedBy
 */
class FinancialAdjustmentRequest extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'ledger_account_id', 'direction', 'amount_minor', 'currency', 'status', 'reason', 'evidence_path', 'reference',
        'requested_by_id', 'decided_by_id', 'decided_at', 'decision_reason', 'journal_entry_id', 'idempotency_key',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $request): void {
            $request->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'decided_by_id');
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
            'direction' => AdjustmentDirection::class,
            'amount_minor' => 'integer',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
