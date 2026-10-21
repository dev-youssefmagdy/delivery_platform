<?php

namespace App\Models;

use App\Enums\Booking\OfferKind;
use App\Enums\Booking\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider's offer on a booking (SRS §8.2, §19.3). The row holds state (status, expiry, revision count) and points
 * at its current version; the amount, currency and scope live only in immutable `BookingOfferVersion` rows (Plan I3).
 *
 * @property int $id
 * @property string $uuid
 * @property int $booking_id
 * @property int $provider_profile_id
 * @property int $vehicle_id
 * @property OfferKind $kind
 * @property OfferStatus $status
 * @property int|null $current_version_id
 * @property Carbon $expires_at
 * @property int|null $eta_seconds
 * @property int|null $distance_m
 * @property int $revision_count
 * @property string|null $idempotency_key
 * @property int|null $active_marker
 * @property int|null $winner_marker
 * @property Carbon|null $closed_at
 * @property string|null $close_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Booking $booking
 * @property-read ProviderProfile $providerProfile
 * @property-read Vehicle $vehicle
 * @property-read BookingOfferVersion|null $currentVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BookingOfferVersion> $versions
 */
class BookingOffer extends Model
{
    protected $fillable = [
        'uuid', 'booking_id', 'provider_profile_id', 'vehicle_id', 'kind', 'status', 'current_version_id', 'expires_at', 'eta_seconds',
        'distance_m', 'revision_count', 'idempotency_key', 'active_marker', 'winner_marker', 'closed_at', 'close_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['revision_count' => 0];

    protected static function booted(): void
    {
        static::creating(static function (self $offer): void {
            $offer->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Active and not past its expiry (an offer whose time ran out is not available even before the sweep closes it).
     */
    public function isOpenAt(Carbon $now): bool
    {
        return $this->status->isActive() && $this->expires_at->greaterThan($now);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<BookingOfferVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(BookingOfferVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<BookingOfferVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(BookingOfferVersion::class, 'offer_id')->orderBy('version');
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
            'kind' => OfferKind::class,
            'status' => OfferStatus::class,
            'expires_at' => 'datetime',
            'eta_seconds' => 'integer',
            'distance_m' => 'integer',
            'revision_count' => 'integer',
            'closed_at' => 'datetime',
        ];
    }
}
