<?php

namespace App\DTOs\Pricing;

/**
 * Guidance or the honest reason there is none (`reference_estimate_disabled`: the category does not offer it,
 * `no_fare_policy`: no enabled policy is in effect for the category / zone / moment).
 */
final readonly class PriceGuidanceResult
{
    public const DISABLED = 'reference_estimate_disabled';

    public const NO_POLICY = 'no_fare_policy';

    public const NO_ROUTE = 'no_route';

    public function __construct(public ?PriceGuidance $guidance, public ?string $unavailableReason = null) {}

    public static function of(PriceGuidance $guidance): self
    {
        return new self($guidance);
    }

    public static function unavailable(string $reason): self
    {
        return new self(null, $reason);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function toApi(): ?array
    {
        return $this->guidance?->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return $this->guidance?->toSnapshot() ?? ['available' => false, 'reason' => $this->unavailableReason];
    }
}
