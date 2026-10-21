<?php

namespace Tests\Feature\Catalog;

use App\Models\FarePolicy;
use App\Models\ServiceCategory;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\FarePolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarePolicySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_seeded_service_gets_one_enabled_starting_policy_and_edits_survive_a_reseed(): void
    {
        $this->seed([CurrencySeeder::class, CatalogSeeder::class, FarePolicySeeder::class]);

        $this->assertSame(ServiceCategory::query()->count(), FarePolicy::query()->count());

        foreach (ServiceCategory::query()->get() as $category) {
            $policy = FarePolicy::query()->where('service_category_id', $category->id)->sole();
            $this->assertTrue($policy->active, $category->code);
            $this->assertNull($policy->service_zone_id);
            $this->assertSame('EGP', $policy->currency);
            $this->assertGreaterThan(0, $policy->min_fare_minor);
            $this->assertLessThanOrEqual(10_000, $policy->range_low_bp);
            $this->assertGreaterThanOrEqual(10_000, $policy->range_high_bp);
            $this->assertTrue($policy->isEffectiveAt(now()), $category->code);
        }

        FarePolicy::query()->update(['base_minor' => 123_400]);
        $this->seed(FarePolicySeeder::class);

        $this->assertSame(ServiceCategory::query()->count(), FarePolicy::query()->count());
        $this->assertSame(1, FarePolicy::query()->distinct()->count('base_minor'));
        $this->assertSame(123_400, FarePolicy::query()->firstOrFail()->base_minor);
    }

    public function test_the_surcharge_keys_exist_in_the_form_schemas_they_price(): void
    {
        $this->seed([CurrencySeeder::class, CatalogSeeder::class, FarePolicySeeder::class]);

        foreach (FarePolicy::query()->with('serviceCategory.publishedVersion')->get() as $policy) {
            $keys = array_column($policy->serviceCategory->publishedVersion->snapshot['form_schema']['fields'], 'key');

            foreach ($policy->attribute_surcharges ?? [] as $row) {
                $this->assertContains($row['field'], $keys, $policy->serviceCategory->code.' surcharges an unknown form field');
            }
        }
    }
}
