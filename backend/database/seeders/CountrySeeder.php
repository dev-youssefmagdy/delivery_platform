<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Reference countries. Idempotent: safe to run in every environment.
 */
class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::query()->updateOrCreate(
            ['iso2' => 'EG'],
            [
                'name' => ['ar' => 'مصر', 'en' => 'Egypt'],
                'phone_code' => '+20',
                'currency_code' => 'EGP',
                'timezone' => 'Africa/Cairo',
                'default_locale' => 'ar',
                'active' => true,
            ],
        );
    }
}
