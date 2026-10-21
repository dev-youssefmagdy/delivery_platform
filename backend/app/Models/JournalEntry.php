<?php

namespace App\Models;

use App\Enums\Finance\JournalEntryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use LogicException;

/**
 * A posted, balanced set of journal lines. Immutable: the model refuses updates and deletes, the repository has no
 * such methods, and a mistake is corrected by a compensating entry (`reversal_of_id`), never by editing (Plan I5).
 *
 * @property int $id
 * @property string $uuid
 * @property JournalEntryType $type
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string $description_key
 * @property array<string, mixed>|null $meta
 * @property Carbon $posted_at
 * @property int|null $reversal_of_id
 * @property string|null $created_by_type
 * @property int|null $created_by_id
 * @property string $idempotency_key
 * @property string $fingerprint
 * @property Carbon $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JournalLine> $lines
 * @property-read JournalEntry|null $reversalOf
 * @property-read JournalEntry|null $reversedBy
 */
class JournalEntry extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid', 'type', 'reference_type', 'reference_id', 'description_key', 'meta', 'posted_at', 'reversal_of_id', 'created_by_type',
        'created_by_id', 'idempotency_key', 'fingerprint',
    ];

    protected static function booted(): void
    {
        static::creating(static function (self $entry): void {
            $entry->uuid ??= (string) Str::uuid();
        });

        static::updating(static function (): never {
            throw new LogicException('Journal entries are immutable: post a reversal instead.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Journal entries are immutable: post a reversal instead.');
        });
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /**
     * @return HasOne<JournalEntry, $this>
     */
    public function reversedBy(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
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
            'type' => JournalEntryType::class,
            'meta' => 'array',
            'posted_at' => 'datetime',
            'reference_id' => 'integer',
            'reversal_of_id' => 'integer',
            'created_by_id' => 'integer',
        ];
    }
}
