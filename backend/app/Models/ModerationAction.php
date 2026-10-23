<?php

namespace App\Models;

use App\Enums\Reviews\ModerationActionType;
use App\Enums\Reviews\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One hide / restore / mark-for-review decision on a rating, with the mandatory reason. Append-only; the review text is never edited
 * (SRS §16.12: no rewriting of user reviews).
 *
 * @property int $id
 * @property string $uuid
 * @property int $rating_id
 * @property ModerationActionType $action
 * @property ModerationStatus $from_status
 * @property ModerationStatus $to_status
 * @property string $reason
 * @property int|null $admin_id
 * @property int|null $report_id
 * @property int|null $appeal_id
 * @property Carbon $created_at
 * @property-read Admin|null $admin
 */
class ModerationAction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['uuid', 'rating_id', 'action', 'from_status', 'to_status', 'reason', 'admin_id', 'report_id', 'appeal_id'];

    protected static function booted(): void
    {
        static::creating(static function (self $action): void {
            $action->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<BookingRating, $this>
     */
    public function rating(): BelongsTo
    {
        return $this->belongsTo(BookingRating::class, 'rating_id');
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class)->withTrashed();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['action' => ModerationActionType::class, 'from_status' => ModerationStatus::class, 'to_status' => ModerationStatus::class];
    }
}
