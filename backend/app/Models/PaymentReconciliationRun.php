<?php

namespace App\Models;

use App\Enums\Finance\ReconciliationStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * The record of one `payments:reconcile` run: what was compared with the gateway and every mismatch found. Report
 * only: the ledger and the payments are never corrected automatically by the comparison.
 *
 * @property int $id
 * @property string $uuid
 * @property ReconciliationStatus $status
 * @property Carbon $window_from
 * @property Carbon $window_to
 * @property int $payments_checked
 * @property int $transactions_checked
 * @property int $timed_out
 * @property list<array<string, mixed>>|null $findings
 * @property string $triggered_by_type
 * @property int|null $triggered_by_id
 * @property Carbon $ran_at
 * @property Carbon $created_at
 */
class PaymentReconciliationRun extends Model
{
    use Auditable;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['uuid', 'status', 'window_from', 'window_to', 'payments_checked', 'transactions_checked', 'timed_out', 'findings', 'triggered_by_type', 'triggered_by_id', 'ran_at'];

    protected static function booted(): void
    {
        static::creating(static function (self $run): void {
            $run->uuid ??= (string) Str::uuid();
        });

        static::updating(static function (): never {
            throw new LogicException('Reconciliation runs are never edited.');
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
            'status' => ReconciliationStatus::class,
            'window_from' => 'datetime',
            'window_to' => 'datetime',
            'findings' => 'array',
            'ran_at' => 'datetime',
        ];
    }
}
