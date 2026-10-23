<?php

namespace App\Models;

use App\Enums\Reviews\RatingReportReason;
use App\Enums\Reviews\RatingReportStatus;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A person's report of a rating they received (SRS §15, §16.12). `details` is the person's own text: scrubbed on account deletion.
 *
 * @property int $id
 * @property string $uuid
 * @property int $rating_id
 * @property string $reporter_type
 * @property int $reporter_id
 * @property int $reporter_user_id
 * @property RatingReportReason $reason
 * @property string|null $details
 * @property RatingReportStatus $status
 * @property int|null $handled_by_admin_id
 * @property Carbon|null $handled_at
 * @property string|null $resolution_note
 * @property Carbon $created_at
 * @property-read BookingRating $rating
 */
class RatingReport extends Model
{
    use Auditable;

    protected $fillable = ['uuid', 'rating_id', 'reporter_type', 'reporter_id', 'reporter_user_id', 'reason', 'details', 'status', 'handled_by_admin_id', 'handled_at', 'resolution_note'];

    /**
     * @var list<string>
     */
    protected array $auditExcluded = ['details'];

    protected static function booted(): void
    {
        static::creating(static function (self $report): void {
            $report->uuid ??= (string) Str::uuid();
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
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'handled_by_admin_id')->withTrashed();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['reason' => RatingReportReason::class, 'status' => RatingReportStatus::class, 'handled_at' => 'datetime'];
    }
}
