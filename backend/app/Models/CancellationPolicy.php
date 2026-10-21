<?php

namespace App\Models;

use App\Support\Audit\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Cancellation rules of a service category (optionally one zone): free grace period after selection, free waiting
 * time, the fee per actor and stage, and the reason categories each side may use (SRS §8.4). A booking copies the
 * policy it was created under into its snapshot and a cancellation is decided from that copy (Plan I4); fees are
 * only computed and snapshotted until the ledger exists (P3.5).
 *
 * @property int $id
 * @property string $name
 * @property int|null $service_category_id
 * @property int|null $service_zone_id
 * @property int $free_grace_seconds
 * @property int $free_wait_seconds
 * @property list<array{actor: string, stage: string, fee_type: string, value: int, cap_minor: int|null, recipient: string}> $rules
 * @property list<string> $customer_reasons
 * @property list<string> $provider_reasons
 * @property bool $active
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property string|null $notes
 * @property int $lock_version
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ServiceCategory|null $serviceCategory
 * @property-read ServiceZone|null $serviceZone
 */
class CancellationPolicy extends Model
{
    use Auditable;

    protected $fillable = [
        'name', 'service_category_id', 'service_zone_id', 'free_grace_seconds', 'free_wait_seconds', 'rules', 'customer_reasons',
        'provider_reasons', 'active', 'effective_from', 'effective_to', 'notes', 'lock_version',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['free_grace_seconds' => 120, 'free_wait_seconds' => 300, 'active' => true, 'lock_version' => 0];

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<ServiceZone, $this>
     */
    public function serviceZone(): BelongsTo
    {
        return $this->belongsTo(ServiceZone::class);
    }

    public function isEffectiveAt(Carbon $at): bool
    {
        return $this->active && $this->effective_from->lessThanOrEqualTo($at) && ($this->effective_to === null || $this->effective_to->greaterThan($at));
    }

    /**
     * The values a booking stores so a cancellation is decided by the rules it was made under.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'id' => $this->id,
            'lock_version' => $this->lock_version,
            'name' => $this->name,
            'free_grace_seconds' => $this->free_grace_seconds,
            'free_wait_seconds' => $this->free_wait_seconds,
            'rules' => $this->rules,
            'customer_reasons' => $this->customer_reasons,
            'provider_reasons' => $this->provider_reasons,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'free_grace_seconds' => 'integer',
            'free_wait_seconds' => 'integer',
            'rules' => 'array',
            'customer_reasons' => 'array',
            'provider_reasons' => 'array',
            'active' => 'boolean',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
