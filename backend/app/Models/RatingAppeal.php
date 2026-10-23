<?php

namespace App\Models;

use App\Enums\Reviews\AppealGrounds;
use App\Enums\Reviews\AppealStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An appeal against a moderation outcome (the person's review was hidden) or a low rating they received (SRS §15). `message` is the
 * person's own text: scrubbed on account deletion.
 *
 * @property int $id
 * @property string $uuid
 * @property int $rating_id
 * @property string $appellant_type
 * @property int $appellant_id
 * @property int $appellant_user_id
 * @property AppealGrounds $grounds
 * @property string $message
 * @property AppealStatus $status
 * @property int|null $decided_by_admin_id
 * @property Carbon|null $decided_at
 * @property string|null $decision_reason
 * @property Carbon $created_at
 * @property-read BookingRating $rating
 */
class RatingAppeal extends Model
{
    use Auditable;

    protected $fillable = ['uuid', 'rating_id', 'appellant_type', 'appellant_id', 'appellant_user_id', 'grounds', 'message', 'status', 'decided_by_admin_id', 'decided_at', 'decision_reason'];

    /**
     * @var list<string>
     */
    protected array $auditExcluded = ['message'];

    protected static function booted(): void
    {
        static::creating(static function (self $appeal): void {
            $appeal->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
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
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'decided_by_admin_id')->withTrashed();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['grounds' => AppealGrounds::class, 'status' => AppealStatus::class, 'decided_at' => 'datetime'];
    }
}
