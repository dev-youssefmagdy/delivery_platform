<?php

namespace App\DTOs\Pricing;

use App\Enums\Pricing\FareFormula;
use App\Enums\Pricing\SurchargeMode;
use App\Models\FarePolicy;

/**
 * The numbers the calculator needs, detached from the model: built from a live policy when a customer asks for
 * guidance, and from a booking's stored snapshot when an estimate has to be explained or recomputed later (Plan I4).
 */
final readonly class FarePolicyValues
{
    /**
     * @param list<array{field: string, mode: string, amount_minor: int, match?: string|null, label?: array<string, string|null>}> $surcharges
     */
    public function __construct(
        public ?int $policyId,
        public FareFormula $formula,
        public string $currency,
        public int $baseMinor,
        public int $perKmMinor,
        public int $perMinMinor,
        public int $minFareMinor,
        public int $stopFeeMinor,
        public array $surcharges,
        public int $roundingStepMinor,
        public int $rangeLowBp,
        public int $rangeHighBp,
    ) {}

    public static function fromModel(FarePolicy $policy): self
    {
        return new self(
            $policy->id,
            $policy->formula_version,
            $policy->currency,
            $policy->base_minor,
            $policy->per_km_minor,
            $policy->per_min_minor,
            $policy->min_fare_minor,
            $policy->stop_fee_minor,
            $policy->attribute_surcharges ?? [],
            $policy->rounding_step_minor,
            $policy->range_low_bp,
            $policy->range_high_bp,
        );
    }

    /**
     * @param array<string, mixed> $snapshot a `FarePolicy::snapshot()`
     */
    public static function fromSnapshot(array $snapshot): self
    {
        /** @var list<array{field: string, mode: string, amount_minor: int, match?: string|null, label?: array<string, string|null>}> $surcharges */
        $surcharges = (array) ($snapshot['attribute_surcharges'] ?? []);

        return new self(
            isset($snapshot['id']) ? (int) $snapshot['id'] : null,
            FareFormula::from((int) ($snapshot['formula_version'] ?? 1)),
            (string) $snapshot['currency'],
            (int) $snapshot['base_minor'],
            (int) $snapshot['per_km_minor'],
            (int) $snapshot['per_min_minor'],
            (int) $snapshot['min_fare_minor'],
            (int) $snapshot['stop_fee_minor'],
            $surcharges,
            (int) $snapshot['rounding_step_minor'],
            (int) $snapshot['range_low_bp'],
            (int) $snapshot['range_high_bp'],
        );
    }

    public function surchargeMode(int $index): SurchargeMode
    {
        return SurchargeMode::from($this->surcharges[$index]['mode']);
    }
}
