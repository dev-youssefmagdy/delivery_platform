<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'iso2' => strtoupper(fake()->unique()->lexify('??')),
            'name' => ['ar' => fake()->country(), 'en' => fake('en_US')->country()],
            'phone_code' => '+'.fake()->numberBetween(1, 999),
            'currency_code' => fn (): string => Currency::factory()->create()->code,
            'timezone' => 'UTC',
            'default_locale' => 'ar',
            'active' => true,
        ];
    }
}
