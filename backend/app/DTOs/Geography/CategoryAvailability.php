<?php

namespace App\DTOs\Geography;

use App\Enums\Geo\AvailabilityReason;
use App\Models\ServiceCategory;
use Carbon\CarbonInterface;
use stdClass;

/**
 * One category as offered by a zone at a given moment.
 */
final readonly class CategoryAvailability
{
    /**
     * @param array<string, mixed> $overrides the zone overrides (min_fare_minor, max_fare_minor, dispatch_radius_steps_km, hours)
     */
    public function __construct(
        public ServiceCategory $category,
        public AvailabilityReason $reason,
        public array $overrides = [],
        public ?CarbonInterface $opensAt = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->reason === AvailabilityReason::Available;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service_category_id' => $this->category->id,
            'code' => $this->category->code,
            'available' => $this->isAvailable(),
            'reason' => $this->reason->value,
            'opens_at' => $this->opensAt?->toIso8601String(),
            'overrides' => $this->overrides === [] ? new stdClass : $this->overrides,
        ];
    }
}
