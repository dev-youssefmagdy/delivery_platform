<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Reference currencies. Idempotent: safe to run in every environment.
 */
class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::query()->updateOrCreate(
            ['code' => 'EGP'],
            ['exponent' => 2, 'symbol_ar' => 'ج.م', 'symbol_en' => 'EGP', 'active' => true],
        );
    }
}
