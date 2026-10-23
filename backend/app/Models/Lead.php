<?php

namespace App\Models;

use App\Enums\Lead\LeadStatus;
use App\Enums\Lead\LeadType;
use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An onboarding lead created by the public "become a provider" form (SRS 17.3). Contact data and interest only, never documents.
 * Contact fields are excluded from the audit snapshot.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $reference
 * @property LeadType $type
 * @property LeadStatus $status
 * @property string $name
 * @property string $phone_e164
 * @property string|null $email
 * @property int|null $city_id
 * @property string|null $city_other
 * @property int|null $service_category_id
 * @property string|null $vehicle_note
 * @property string|null $message
 * @property string $locale
 * @property string $source
 * @property Carbon $consented_at
 * @property string|null $ip_hash
 * @property string $duplicate_key
 * @property int|null $assigned_admin_id
 * @property string|null $notes
 * @property string|null $status_reason
 * @property Carbon|null $status_changed_at
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read City|null $city
 * @property-read ServiceCategory|null $serviceCategory
 * @property-read Admin|null $assignedAdmin
 */
class Lead extends Model
{
    use Auditable;

    /** @var list<string> */
    protected $fillable = [
        'uuid', 'reference', 'type', 'status', 'name', 'phone_e164', 'email', 'city_id', 'city_other', 'service_category_id', 'vehicle_note',
        'message', 'locale', 'source', 'consented_at', 'ip_hash', 'duplicate_key', 'assigned_admin_id', 'notes', 'status_reason',
        'status_changed_at', 'lock_version',
    ];

    /** @var list<string> */
    protected array $auditExcluded = ['name', 'phone_e164', 'email', 'message', 'notes', 'ip_hash', 'duplicate_key', 'vehicle_note', 'city_other'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'new', 'type' => 'provider_onboarding', 'source' => 'website', 'lock_version' => 0];

    protected static function booted(): void
    {
        static::creating(static function (self $lead): void {
            $lead->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'consented_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
