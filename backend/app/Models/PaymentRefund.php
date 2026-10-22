<?php

namespace App\Models;

use App\Enums\Payments\RefundStatus;
use App\Support\Audit\Auditable;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A refund of a collected online payment. Money moves back through the gateway and the ledger gets compensating
 * entries only after the gateway confirms (never an edit of the original entries). Amounts at or above the
 * dual-approval threshold need a second admin: `requested_by_id` and `decided_by_id` must differ.
 *
 * @property int $id
 * @property string $uuid
 * @property int $payment_id
 * @property int $amount_minor
 * @property string $currency
 * @property Money|null $amount
 * @property RefundStatus $status
 * @property string $reason
 * @property bool $requires_dual_approval
 * @property int $requested_by_id
 * @property int|null $decided_by_id
 * @property Carbon|null $decided_at
 * @property string|null $decision_reason
 * @property string|null $gateway_reference
 * @property string|null $failure_code
 * @property int|null $journal_entry_id
 * @property string $idempotency_key
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Payment $payment
 * @property-read Admin|null $requestedBy
 * @property-read Admin|null $decidedBy
 */
class PaymentRefund extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'payment_id', 'amount_minor', 'currency', 'status', 'reason', 'requires_dual_approval', 'requested_by_id', 'decided_by_id',
        'decided_at', 'decision_reason', 'gateway_reference', 'failure_code', 'journal_entry_id', 'idempotency_key', 'completed_at',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $refund): void {
            $refund->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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
            'amount_minor' => 'integer',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'status' => RefundStatus::class,
            'requires_dual_approval' => 'boolean',
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
