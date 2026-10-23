<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Reference data first (idempotent), then environment-specific demo data in later milestones.
     */
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            CountrySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            CatalogSeeder::class,
            FarePolicySeeder::class,
            GeographySeeder::class,
            DocumentTypeSeeder::class,
            ProviderSeeder::class,
            CouponSeeder::class,
            NotificationTemplateSeeder::class,
            RestrictedGoodsPolicySeeder::class,
            ContentSeeder::class,
        ]);
    }
}
