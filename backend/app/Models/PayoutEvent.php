<?php

namespace App\Models;

use App\Enums\Payout\PayoutStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only history of a payout request: every status change with its actor and note (the payout's own timeline).
 *
 * @property int $id
 * @property int $payout_request_id
 * @property PayoutStatus|null $from_status
 * @property PayoutStatus $to_status
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string|null $note
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class PayoutEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['payout_request_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'note', 'meta'];

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Payout events are append-only.');
        });
        static::deleting(static function (): void {
            throw new LogicException('Payout events are append-only.');
        });
    }

    /**
     * @return BelongsTo<PayoutRequest, $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class, 'payout_request_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'from_status' => PayoutStatus::class,
            'to_status' => PayoutStatus::class,
            'meta' => 'array',
        ];
    }
}
