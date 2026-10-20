<?php

namespace Tests\Feature\Catalog;

use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Models\ServiceFamily;
use App\Models\VehicleCategory;
use App\Services\Catalog\Forms\FormSchemaValidator;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The starter catalog (SRS §28) is complete, valid and idempotent.
 */
class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_documented_services_published_and_active(): void
    {
        $this->seed(CatalogSeeder::class);

        $codes = ServiceCategory::query()->orderBy('sort_order')->pluck('code')->all();
        $this->assertSame([
            'ride_tuk_tuk', 'ride_taxi', 'ride_economy', 'ride_standard', 'courier_motorcycle',
            'moving_pickup_truck', 'moving_furniture_truck', 'freight_truck',
        ], $codes);
        $this->assertSame(4, ServiceFamily::query()->where('active', true)->count());
        $this->assertSame(8, VehicleCategory::query()->where('active', true)->count());

        foreach (ServiceCategory::query()->with(['publishedVersion.formSchema', 'vehicleCategories'])->get() as $category) {
            $this->assertTrue($category->active, $category->code);
            $this->assertNotNull($category->publishedVersion, $category->code);
            $this->assertNotEmpty($category->vehicleCategories, $category->code);
            $this->assertSame([], app(FormSchemaValidator::class)->validate($category->publishedVersion->formSchema->fields), $category->code);
            $this->assertNotEmpty($category->getTranslation('name', 'ar'), $category->code);
        }
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(8, ServiceCategory::query()->count());
        $this->assertSame(8, ServiceCategoryVersion::query()->count());
        $this->assertSame(4, ServiceFamily::query()->count());
    }

    public function test_every_seeded_label_has_an_arabic_text(): void
    {
        $this->seed(CatalogSeeder::class);

        foreach (ServiceCategoryVersion::query()->get() as $version) {
            foreach ($version->snapshot['form_schema']['fields'] as $field) {
                $this->assertNotEmpty($field['label']['ar'] ?? null, $field['key']);

                foreach ($field['options'] as $option) {
                    $this->assertNotEmpty($option['label']['ar'] ?? null, $field['key'].'.'.$option['value']);
                }
            }
        }
    }
}
