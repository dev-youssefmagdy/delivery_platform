<?php

namespace App\Models;

use App\Enums\Provider\ServiceApprovalStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A service category a provider asked to serve and whether an admin approved it (`provider_service_category`).
 *
 * @property int $id
 * @property int $provider_profile_id
 * @property int $service_category_id
 * @property ServiceApprovalStatus $status
 * @property int|null $decided_by_admin_id
 * @property Carbon|null $decided_at
 * @property string|null $decision_reason
 * @property-read ServiceCategory $serviceCategory
 * @property-read ProviderProfile $providerProfile
 */
class ProviderServiceCategory extends Pivot
{
    protected $table = 'provider_service_category';

    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<ProviderProfile, $this>
     */
    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => ServiceApprovalStatus::class, 'decided_at' => 'datetime'];
    }
}
