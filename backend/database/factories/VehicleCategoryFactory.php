<?php

namespace Database\Factories;

use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleCategory>
 */
class VehicleCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'vehicle_'.fake()->unique()->bothify('????##'),
            'name' => ['ar' => 'مركبة '.fake()->unique()->numerify('####'), 'en' => 'Vehicle '.fake()->unique()->numerify('####')],
            'description' => null,
            'icon' => 'car',
            'map_marker' => 'car',
            'passenger_capacity' => 4,
            'luggage_capacity' => 2,
            'max_payload_kg' => null,
            'max_volume_m3' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
