<?php

namespace Database\Factories;

use App\Enums\Pricing\FareFormula;
use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A simple EGP policy: 10.00 base, 4.00 per km, 0.50 per minute, 20.00 minimum, 5.00 per stop, rounded to whole pounds.
 *
 * @extends Factory<FarePolicy>
 */
class FarePolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'service_zone_id' => null,
            'formula_version' => FareFormula::V1,
            'currency' => 'EGP',
            'base_minor' => 1000,
            'per_km_minor' => 400,
            'per_min_minor' => 50,
            'min_fare_minor' => 2000,
            'stop_fee_minor' => 500,
            'attribute_surcharges' => [],
            'rounding_step_minor' => 100,
            'range_low_bp' => 8500,
            'range_high_bp' => 12500,
            'active' => true,
            'effective_from' => now()->subDay(),
            'effective_to' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
