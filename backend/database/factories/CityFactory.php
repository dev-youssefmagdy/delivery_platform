<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Support\Geo\GeoPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => ['ar' => 'مدينة '.fake()->unique()->numerify('####'), 'en' => 'City '.fake()->unique()->numerify('####')],
            'timezone' => 'Africa/Cairo',
            'center' => GeoPoint::make(30.0444, 31.2357),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
