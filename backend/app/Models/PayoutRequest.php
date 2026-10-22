<?php

namespace App\Models;

use App\Enums\Payout\PayoutStatus;
use App\Support\Audit\Auditable;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider's withdrawal request. The amount is reserved in the ledger (provider wallet -> payout clearing) when the
 * request is made. Amounts at or above the dual-approval threshold need two different approving admins.
 *
 * @property int $id
 * @property string $uuid
 * @property int $provider_profile_id
 * @property int|null $payout_method_id
 * @property array<string, mixed> $method_snapshot
 * @property int $amount_minor
 * @property Money|null $amount
 * @property int $fee_minor
 * @property int $net_minor
 * @property string $currency
 * @property PayoutStatus $status
 * @property bool $requires_dual_approval
 * @property int|null $reserve_entry_id
 * @property int|null $release_entry_id
 * @property int|null $paid_entry_id
 * @property int|null $reviewed_by_id
 * @property Carbon|null $reviewed_at
 * @property int|null $first_approved_by_id
 * @property Carbon|null $first_approved_at
 * @property int|null $approved_by_id
 * @property Carbon|null $approved_at
 * @property string|null $decision_note
 * @property string|null $rejection_reason
 * @property int|null $payout_batch_id
 * @property int|null $processing_by_id
 * @property Carbon|null $processing_at
 * @property string|null $transfer_reference
 * @property string|null $evidence_path
 * @property Carbon|null $paid_at
 * @property string|null $failure_reason
 * @property Carbon|null $closed_at
 * @property string $idempotency_key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ProviderProfile $provider
 * @property-read ProviderPayoutMethod|null $method
 * @property-read PayoutBatch|null $batch
 * @property-read Admin|null $reviewedBy
 * @property-read Admin|null $approvedBy
 * @property-read Admin|null $firstApprovedBy
 */
class PayoutRequest extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'provider_profile_id', 'payout_method_id', 'method_snapshot', 'amount_minor', 'fee_minor', 'net_minor', 'currency', 'status',
        'requires_dual_approval', 'reserve_entry_id', 'release_entry_id', 'paid_entry_id', 'reviewed_by_id', 'reviewed_at', 'first_approved_by_id',
        'first_approved_at', 'approved_by_id', 'approved_at', 'decision_note', 'rejection_reason', 'payout_batch_id', 'processing_by_id',
        'processing_at', 'transfer_reference', 'evidence_path', 'paid_at', 'failure_reason', 'closed_at', 'idempotency_key',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $payout): void {
            $payout->uuid ??= (string) Str::uuid();
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
     * @return BelongsTo<ProviderPayoutMethod, $this>
     */
    public function method(): BelongsTo
    {
        return $this->belongsTo(ProviderPayoutMethod::class, 'payout_method_id')->withTrashed();
    }

    /**
     * @return BelongsTo<PayoutBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class, 'payout_batch_id');
    }

    /**
     * @return HasMany<PayoutEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PayoutEvent::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function firstApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'first_approved_by_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_id');
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
            'amount_minor' => 'integer',
            'fee_minor' => 'integer',
            'net_minor' => 'integer',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'status' => PayoutStatus::class,
            'method_snapshot' => 'array',
            'requires_dual_approval' => 'boolean',
            'reviewed_at' => 'datetime',
            'first_approved_at' => 'datetime',
            'approved_at' => 'datetime',
            'processing_at' => 'datetime',
            'paid_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
