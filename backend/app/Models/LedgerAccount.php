<?php

namespace App\Models;

use App\Enums\Finance\LedgerAccountType;
use App\Support\Audit\Auditable;
use App\Support\Money\MoneyCast;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One account of the ledger (SRS §12.3). The balance is a cache of its journal lines in the account's natural sign,
 * maintained only by LedgerService under a row lock and verified by `ledger:reconcile` (Plan I6). Accounts are never
 * deleted; the negative-balance policy (`allow_negative`, `credit_limit_minor`) is part of the row.
 *
 * @property int $id
 * @property string $uuid
 * @property LedgerAccountType $type
 * @property string|null $owner_type
 * @property int|null $owner_id
 * @property string $scope_key
 * @property string $currency
 * @property int $balance_minor
 * @property Money|null $balance
 * @property bool $allow_negative
 * @property int|null $credit_limit_minor
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LedgerAccount extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'type', 'owner_type', 'owner_id', 'scope_key', 'currency', 'balance_minor', 'allow_negative', 'credit_limit_minor', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['balance_minor' => 0, 'allow_negative' => false, 'lock_version' => 0];

    protected static function booted(): void
    {
        static::creating(static function (self $account): void {
            $account->uuid ??= (string) Str::uuid();
        });
    }

    public static function scopeKeyFor(LedgerAccountType $type, ?int $ownerId): string
    {
        return $type->isOwned() ? ($type->ownerType().':'.$ownerId) : 'platform';
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('id');
    }

    /**
     * The lowest balance the account may reach, or null when it may go as far down as it likes.
     */
    public function lowestAllowedBalance(): ?int
    {
        if (! $this->allow_negative) {
            return 0;
        }

        return $this->credit_limit_minor === null ? null : -$this->credit_limit_minor;
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
            'type' => LedgerAccountType::class,
            'balance_minor' => 'integer',
            'balance' => MoneyCast::class.':balance_minor,currency',
            'allow_negative' => 'boolean',
            'credit_limit_minor' => 'integer',
            'lock_version' => 'integer',
        ];
    }
}
