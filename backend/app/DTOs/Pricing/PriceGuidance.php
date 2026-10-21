<?php

namespace App\DTOs\Pricing;

use App\Enums\Pricing\FareFormula;
use App\Support\Money\Money;

/**
 * Reference price guidance (SRS §9.1): a range and a recommendation with the factors behind them. It is advice for the
 * customer's own proposal, never a quote and never what a provider must accept.
 */
final readonly class PriceGuidance
{
    /**
     * @param list<PriceFactorLine> $factors
     */
    public function __construct(
        public Money $low,
        public Money $recommended,
        public Money $high,
        public array $factors,
        public FareFormula $formula,
        public ?int $farePolicyId,
        public int $distanceMeters,
        public int $durationSeconds,
        public int $stopCount,
    ) {}

    /**
     * API shape.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->recommended->currency->code,
            'low_minor' => $this->low->amount,
            'recommended_minor' => $this->recommended->amount,
            'high_minor' => $this->high->amount,
            'factors' => array_map(static fn (PriceFactorLine $line): array => $line->toArray(), $this->factors),
            'formula_version' => $this->formula->value,
            'notice' => (string) __('api.pricing.guidance_notice'),
        ];
    }

    /**
     * Locale-independent form stored on the booking (`reference_estimate`).
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'available' => true,
            'currency' => $this->recommended->currency->code,
            'low_minor' => $this->low->amount,
            'recommended_minor' => $this->recommended->amount,
            'high_minor' => $this->high->amount,
            'factors' => array_map(static fn (PriceFactorLine $line): array => $line->toSnapshot(), $this->factors),
            'formula_version' => $this->formula->value,
            'fare_policy_id' => $this->farePolicyId,
            'inputs' => ['distance_m' => $this->distanceMeters, 'duration_s' => $this->durationSeconds, 'stops' => $this->stopCount],
        ];
    }
}
