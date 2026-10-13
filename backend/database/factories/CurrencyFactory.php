<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'code' => $code,
            'exponent' => 2,
            'symbol_ar' => $code,
            'symbol_en' => $code,
            'active' => true,
        ];
    }

    public function egp(): static
    {
        return $this->state(['code' => 'EGP', 'exponent' => 2, 'symbol_ar' => 'ج.م', 'symbol_en' => 'EGP']);
    }
}
