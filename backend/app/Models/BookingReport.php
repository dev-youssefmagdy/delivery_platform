<?php

namespace App\Models;

use App\Enums\Booking\ReportCategory;
use App\Enums\Booking\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A problem reported about a booking by one of its sides. Stored for support; P3.7 migrates open rows into tickets.
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property string $reporter_type
 * @property int $reporter_id
 * @property ReportCategory $category
 * @property string $description
 * @property ReportStatus $status
 * @property Carbon $created_at
 */
class BookingReport extends Model
{
    protected $fillable = ['uuid', 'booking_id', 'reporter_type', 'reporter_id', 'category', 'description', 'status'];

    protected static function booted(): void
    {
        static::creating(static function (self $report): void {
            $report->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['category' => ReportCategory::class, 'status' => ReportStatus::class];
    }
}
