<?php

namespace App\Models;

use App\Enums\Location\OfflineReason;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Support\Audit\Auditable;
use Database\Factories\ProviderProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A provider application and, once approved, the provider's working profile (SRS §7.1, §19.1). The status is only
 * changed by `ProviderStatusService`; `online_eligible` is never stored (it is computed by `ProviderEligibilityService`).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property ProviderType|null $provider_type
 * @property ProviderStatus $status
 * @property string|null $status_reason shown to the provider for rejections / more-information requests and to admins for suspensions
 * @property Carbon|null $status_changed_at
 * @property Carbon|null $status_expires_at when a temporary suspension ends
 * @property int|null $city_id
 * @property Carbon|null $date_of_birth
 * @property string $rating_avg decimal string
 * @property int $rating_count
 * @property int $completed_jobs
 * @property int $requests_seen
 * @property int $requests_accepted
 * @property bool $is_online
 * @property Carbon|null $online_since
 * @property OfflineReason|null $last_offline_reason
 * @property int|null $current_vehicle_id
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property int|null $assigned_admin_id
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read City|null $city
 */
class ProviderProfile extends Model
{
    use Auditable;

    /** @use HasFactory<ProviderProfileFactory> */
    use HasFactory;

    /**
     * Mass assignment is limited to what the applicant may edit; the status and counters are set by services only.
     */
    protected $fillable = ['user_id', 'provider_type', 'city_id', 'date_of_birth'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'rating_avg' => 0,
        'rating_count' => 0,
        'completed_jobs' => 0,
        'requests_seen' => 0,
        'requests_accepted' => 0,
        'is_online' => false,
        'lock_version' => 0,
    ];

    /**
     * The birth date is personal data and never goes into the audit log.
     *
     * @var list<string>
     */
    protected array $auditExcluded = ['date_of_birth'];

    protected static function booted(): void
    {
        static::creating(static function (self $profile): void {
            $profile->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        // Anonymised accounts are soft-deleted; the profile and its history must stay readable.
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasMany<ProviderDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ProviderDocument::class);
    }

    /**
     * @return HasMany<ProviderServiceCategory, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(ProviderServiceCategory::class);
    }

    /**
     * @return BelongsToMany<ServiceCategory, $this, ProviderServiceCategory>
     */
    public function serviceCategories(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class, 'provider_service_category')
            ->using(ProviderServiceCategory::class)
            ->withPivot(['status', 'decided_at', 'decision_reason'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ProviderStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(ProviderStatusEvent::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider_type' => ProviderType::class,
            'status' => ProviderStatus::class,
            'status_changed_at' => 'datetime',
            'status_expires_at' => 'datetime',
            'date_of_birth' => 'date',
            'rating_count' => 'integer',
            'completed_jobs' => 'integer',
            'requests_seen' => 'integer',
            'requests_accepted' => 'integer',
            'is_online' => 'boolean',
            'online_since' => 'datetime',
            'last_offline_reason' => OfflineReason::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
