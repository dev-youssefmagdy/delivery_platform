<?php

namespace App\Models;

use App\Enums\Payout\PayoutBatchStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A transfer run: approved payouts of one currency grouped so finance can export the transfer file and settle them together.
 *
 * @property int $id
 * @property string $uuid
 * @property string $reference
 * @property PayoutBatchStatus $status
 * @property string $currency
 * @property int $payout_count
 * @property int $total_minor
 * @property string|null $notes
 * @property int $created_by_id
 * @property Carbon|null $exported_at
 * @property int|null $exported_by_id
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property-read Admin|null $createdBy
 */
class PayoutBatch extends Model
{
    use Auditable;

    /**
     * @var list<string>
     */
    protected $fillable = ['uuid', 'reference', 'status', 'currency', 'payout_count', 'total_minor', 'notes', 'created_by_id', 'exported_at', 'exported_by_id', 'completed_at'];

    protected static function booted(): void
    {
        static::creating(static function (self $batch): void {
            $batch->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return HasMany<PayoutRequest, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_id');
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
            'status' => PayoutBatchStatus::class,
            'payout_count' => 'integer',
            'total_minor' => 'integer',
            'exported_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
