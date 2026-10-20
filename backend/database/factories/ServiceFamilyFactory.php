<?php

namespace Database\Factories;

use App\Enums\Catalog\ServiceFamilyKind;
use App\Models\ServiceFamily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceFamily>
 */
class ServiceFamilyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'family_'.fake()->unique()->bothify('????##'),
            'name' => ['ar' => 'عائلة '.fake()->unique()->numerify('####'), 'en' => 'Family '.fake()->unique()->numerify('####')],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'kind' => ServiceFamilyKind::PassengerCity,
            'icon' => 'car',
            'sort_order' => fake()->numberBetween(0, 100),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
