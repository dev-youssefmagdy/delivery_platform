<?php

namespace App\DTOs\Catalog;

/**
 * A category's effective dispatch/safety policy: its own value, or the platform setting when it has none.
 */
final readonly class CategoryPolicyData
{
    /**
     * @param list<float> $dispatchRadiusStepsKm
     */
    public function __construct(
        public int $offerTtlSeconds,
        public int $searchTtlSeconds,
        public array $dispatchRadiusStepsKm,
        public int $dispatchBatchSize,
        public bool $pinRequired,
    ) {}

    /**
     * @return array{offer_ttl_seconds: int, search_ttl_seconds: int, dispatch_radius_steps_km: list<float>, dispatch_batch_size: int, pin_required: bool}
     */
    public function toArray(): array
    {
        return [
            'offer_ttl_seconds' => $this->offerTtlSeconds,
            'search_ttl_seconds' => $this->searchTtlSeconds,
            'dispatch_radius_steps_km' => $this->dispatchRadiusStepsKm,
            'dispatch_batch_size' => $this->dispatchBatchSize,
            'pin_required' => $this->pinRequired,
        ];
    }
}
