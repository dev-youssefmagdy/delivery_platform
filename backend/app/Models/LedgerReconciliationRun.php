<?php

namespace App\Models;

use App\Enums\Finance\ReconciliationStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * The record of one `ledger:reconcile` run: what was compared and every mismatch found. Report only; the ledger is
 * never corrected automatically.
 *
 * @property int $id
 * @property string $uuid
 * @property ReconciliationStatus $status
 * @property int $accounts_checked
 * @property int $entries_checked
 * @property list<array<string, mixed>>|null $findings
 * @property string $triggered_by_type
 * @property int|null $triggered_by_id
 * @property Carbon $ran_at
 * @property Carbon $created_at
 */
class LedgerReconciliationRun extends Model
{
    use Auditable;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['uuid', 'status', 'accounts_checked', 'entries_checked', 'findings', 'triggered_by_type', 'triggered_by_id', 'ran_at'];

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
            'accounts_checked' => 'integer',
            'entries_checked' => 'integer',
            'findings' => 'array',
            'triggered_by_id' => 'integer',
            'ran_at' => 'datetime',
        ];
    }
}
