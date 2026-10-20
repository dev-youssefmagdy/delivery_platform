<?php

namespace Tests\Feature\Api;

use App\Enums\Catalog\BookingType;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\VehicleCategory;
use App\Services\Catalog\ServiceCategoryPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET /service-families, /service-categories, /vehicle-categories (Plan P1.4 step 6).
 */
class CatalogEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $attributes
     */
    private function publishedCategory(array $attributes = [], ?ServiceFamily $family = null, ?VehicleCategory $vehicle = null): ServiceCategory
    {
        $category = ServiceCategory::factory()->create($attributes + ['service_family_id' => $family ?? ServiceFamily::factory()]);
        $category->vehicleCategories()->attach($vehicle ?? VehicleCategory::factory()->create());
        $category->draftSchema()->firstOrFail()->update(['fields' => [[
            'key' => 'passengers', 'type' => 'integer', 'label' => ['ar' => 'عدد الركاب', 'en' => 'Passengers'], 'help' => null,
            'required' => true, 'min' => 1, 'max' => 4, 'options' => [], 'visible_if' => null,
        ]]]);
        app(ServiceCategoryPublisher::class)->publish($category, 'Release', null);

        return $category->refresh()->forceFill(['active' => true]);
    }

    private function activate(ServiceCategory $category): void
    {
        $category->forceFill(['active' => true])->save();
    }

    public function test_only_active_published_categories_of_active_families_are_listed(): void
    {
        $visible = $this->publishedCategory();
        $this->activate($visible);
        $inactive = $this->publishedCategory();
        $unpublished = ServiceCategory::factory()->active()->create();
        $hiddenFamily = ServiceFamily::factory()->inactive()->create();
        $inHiddenFamily = $this->publishedCategory(family: $hiddenFamily);
        $this->activate($inHiddenFamily);
        $inactive->forceFill(['active' => false])->save();

        $response = $this->getJson('/api/v1/service-categories')->assertOk();

        $this->assertSame([$visible->id], array_column($response->json('data'), 'id'));
        $this->assertNotContains($unpublished->id, array_column($response->json('data'), 'id'));
    }

    public function test_category_resource_exposes_the_published_version_and_form_schema(): void
    {
        $category = $this->publishedCategory(['code' => 'ride_x', 'max_passengers' => 4, 'offer_ttl_seconds' => 45]);
        $this->activate($category);

        $this->getJson('/api/v1/service-categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $category->id)
            ->assertJsonPath('data.0.code', 'ride_x')
            ->assertJsonPath('data.0.version.id', $category->published_version_id)
            ->assertJsonPath('data.0.version.number', 1)
            ->assertJsonPath('data.0.dispatch.offer_ttl_seconds', 45)
            ->assertJsonPath('data.0.limits.max_passengers', 4)
            ->assertJsonPath('data.0.payments.payment_methods', ['cash'])
            ->assertJsonPath('data.0.form_schema.version', 1)
            ->assertJsonPath('data.0.form_schema.fields.0.key', 'passengers')
            ->assertJsonPath('data.0.form_schema.fields.0.label.ar', 'عدد الركاب')
            ->assertJsonPath('data.0.vehicle_category_ids.0', $category->vehicleCategories()->firstOrFail()->id)
            ->assertJsonStructure(['data' => [['id', 'code', 'version' => ['id', 'number', 'published_at'], 'family' => ['id', 'code', 'kind', 'name'], 'name', 'description', 'icon', 'map_marker', 'sort_order', 'booking', 'pricing', 'dispatch', 'limits', 'safety', 'payments', 'vehicle_category_ids', 'vehicle_categories', 'form_schema' => ['version', 'fields']]]]);
    }

    public function test_clients_keep_seeing_the_published_version_until_the_next_publish(): void
    {
        $category = $this->publishedCategory();
        $this->activate($category);
        $category->update(['max_passengers' => 9, 'name' => ['ar' => 'اسم مسودة', 'en' => 'Draft name']]);

        $this->getJson('/api/v1/service-categories')
            ->assertJsonPath('data.0.limits.max_passengers', 4)
            ->assertJsonMissing(['ar' => 'اسم مسودة']);

        app(ServiceCategoryPublisher::class)->publish($category->fresh(['publishedVersion']), 'Update', null);
        $this->getJson('/api/v1/service-categories')
            ->assertJsonPath('data.0.limits.max_passengers', 9)
            ->assertJsonPath('data.0.version.number', 2)
            ->assertJsonPath('data.0.name.ar', 'اسم مسودة');
    }

    public function test_filters_by_booking_type_and_family_and_validates_coordinates(): void
    {
        $ride = $this->publishedCategory(['allowed_booking_types' => [BookingType::OnDemand->value]]);
        $parcel = $this->publishedCategory(['allowed_booking_types' => [BookingType::Parcel->value]]);
        $this->activate($ride);
        $this->activate($parcel);

        $this->getJson('/api/v1/service-categories?booking_type=parcel')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $parcel->id);
        $this->getJson('/api/v1/service-categories?service_family_id='.$ride->service_family_id)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ride->id);
        // With a position only the serving zone's categories are returned (details: GeographyEndpointsTest).
        $this->getJson('/api/v1/service-categories?lat=30.04&lng=31.23&booking_type=on_demand')->assertOk()->assertJsonCount(0, 'data');
        $zone = \App\Models\ServiceZone::factory()->create();
        \App\Models\ZoneServiceCategory::query()->create(['service_zone_id' => $zone->id, 'service_category_id' => $ride->id, 'active' => true]);
        $this->getJson('/api/v1/service-categories?lat=30.04&lng=31.23&booking_type=on_demand')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ride->id);
        $this->getJson('/api/v1/service-categories?booking_type=teleport')->assertStatus(422)->assertJsonValidationErrors(['booking_type']);
        $this->getJson('/api/v1/service-categories?lat=95&lng=31')->assertStatus(422)->assertJsonValidationErrors(['lat']);
        $this->getJson('/api/v1/service-categories?lat=30')->assertStatus(422)->assertJsonValidationErrors(['lng']);
    }

    public function test_families_list_only_families_with_visible_categories(): void
    {
        $category = $this->publishedCategory();
        $this->activate($category);
        $emptyFamily = ServiceFamily::factory()->create();
        $inactiveFamily = ServiceFamily::factory()->inactive()->create();
        $this->activate($this->publishedCategory(family: $inactiveFamily));

        $response = $this->getJson('/api/v1/service-families')->assertOk();

        $this->assertSame([$category->service_family_id], array_column($response->json('data'), 'id'));
        $this->assertNotContains($emptyFamily->id, array_column($response->json('data'), 'id'));
        $response->assertJsonPath('data.0.categories_count', 1)->assertJsonStructure(['data' => [['id', 'code', 'kind', 'name' => ['ar'], 'description', 'icon', 'sort_order', 'categories_count']]]);
    }

    public function test_vehicle_categories_can_be_limited_to_a_service_category(): void
    {
        $vehicle = VehicleCategory::factory()->create(['passenger_capacity' => 3]);
        $category = $this->publishedCategory(vehicle: $vehicle);
        VehicleCategory::factory()->create();
        VehicleCategory::factory()->inactive()->create();

        $this->getJson('/api/v1/vehicle-categories?service_category_id='.$category->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $vehicle->id)->assertJsonPath('data.0.passenger_capacity', 3);
        $this->getJson('/api/v1/vehicle-categories')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/vehicle-categories?service_category_id=abc')->assertStatus(422);
    }

    public function test_responses_are_public_and_etag_cacheable(): void
    {
        $this->activate($this->publishedCategory());

        $first = $this->getJson('/api/v1/service-categories')->assertOk();
        $etag = $first->headers->get('ETag');
        $this->assertNotNull($etag);
        $this->assertStringContainsString('max-age=30', (string) $first->headers->get('Cache-Control'));
        $this->getJson('/api/v1/service-categories', ['If-None-Match' => $etag])->assertStatus(304);
    }
}
