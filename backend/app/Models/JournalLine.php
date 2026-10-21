<?php

namespace App\Models;

use App\Enums\Finance\EntrySide;
use App\Support\Money\Money;
use App\Support\Money\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One debit or credit of a journal entry. Immutable like its entry.
 *
 * @property int $id
 * @property int $journal_entry_id
 * @property int $ledger_account_id
 * @property EntrySide $side
 * @property int $amount_minor
 * @property Money|null $amount
 * @property string $currency
 * @property int $balance_after_minor
 * @property Carbon $created_at
 * @property-read JournalEntry $entry
 * @property-read LedgerAccount $account
 */
class JournalLine extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['journal_entry_id', 'ledger_account_id', 'side', 'amount_minor', 'currency', 'balance_after_minor'];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Journal lines are immutable: post a reversal instead.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Journal lines are immutable: post a reversal instead.');
        });
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'side' => EntrySide::class,
            'amount_minor' => 'integer',
            'amount' => MoneyCast::class.':amount_minor,currency',
            'balance_after_minor' => 'integer',
        ];
    }
}
