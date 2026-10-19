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
            // Never starts with E so it cannot collide with the seeded Egypt (EG).
            'iso2' => fake()->unique()->regexify('[A-DF-Z][A-Z]'),
            'name' => ['ar' => fake()->country(), 'en' => fake('en_US')->country()],
            'phone_code' => '+'.fake()->numberBetween(1, 999),
            'currency_code' => fn (): string => Currency::factory()->create()->code,
            'timezone' => 'UTC',
            'default_locale' => 'ar',
            'active' => true,
        ];
    }
}
