<?php

namespace App\Models;

use App\Enums\Reviews\ModerationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * One side's rating of a completed booking (SRS §15). The rater is the customer (`users.id`) or the provider
 * (`provider_profiles.id`), the ratee the other side; one rating per booking and side (unique index).
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property string $rater_type
 * @property int $rater_id
 * @property string $ratee_type
 * @property int $ratee_id
 * @property int $stars
 * @property list<string>|null $tags
 * @property string|null $comment
 * @property ModerationStatus $moderation_status
 * @property int $reports_count
 * @property Carbon|null $moderated_at
 * @property Carbon $created_at
 */
class BookingRating extends Model
{
    protected $fillable = ['uuid', 'booking_id', 'rater_type', 'rater_id', 'ratee_type', 'ratee_id', 'stars', 'tags', 'comment', 'moderation_status', 'reports_count', 'moderated_at'];

    protected static function booted(): void
    {
        static::creating(static function (self $rating): void {
            $rating->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return HasMany<RatingReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(RatingReport::class, 'rating_id');
    }

    /**
     * @return HasMany<RatingAppeal, $this>
     */
    public function appeals(): HasMany
    {
        return $this->hasMany(RatingAppeal::class, 'rating_id');
    }

    /**
     * @return HasMany<ModerationAction, $this>
     */
    public function moderationActions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'rating_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['stars' => 'integer', 'tags' => 'array', 'moderation_status' => ModerationStatus::class, 'moderated_at' => 'datetime'];
    }
}
