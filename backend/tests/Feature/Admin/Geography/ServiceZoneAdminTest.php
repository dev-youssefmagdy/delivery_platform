<?php

namespace Tests\Feature\Admin\Geography;

use App\DataTables\Admin\Geography\ServiceZonesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Geo\ZoneStatus;
use App\Models\City;
use App\Models\ServiceZone;
use App\Models\ZoneServiceCategory;
use Database\Factories\ServiceZoneFactory;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsGeography;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Service Zones, the availability matrix and the coverage preview.
 */
class ServiceZoneAdminTest extends TestCase
{
    use BuildsGeography;
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.service-zones.index')));
        $this->assertGuestBlocked($this->get(route('admin.coverage-preview.show')));
    }

    public function test_admin_without_permission_gets_403_everywhere(): void
    {
        $this->actingAsAdmin();
        $zone = ServiceZone::factory()->create();

        $this->get(route('admin.service-zones.index'))->assertForbidden();
        $this->getJson(route('admin.service-zones.show', $zone))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.service-zones.index'))->assertForbidden();
        $this->postJson(route('admin.service-zones.store'), $this->payload($zone->city_id))->assertForbidden();
        $this->postJson(route('admin.service-zones.suspend', $zone), ['reason' => 'because reasons'])->assertForbidden();
        $this->putJson(route('admin.service-zones.availability', $zone), ['lock_version' => 0])->assertForbidden();
        $this->postJson(route('admin.service-zones.overlaps'), ['polygon' => '[]'])->assertForbidden();
        $this->get(route('admin.coverage-preview.show'))->assertForbidden();
        $this->postJson(route('admin.coverage-preview.run'), ['origin_lat' => 30.05, 'origin_lng' => 31.25])->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);

        $this->get(route('admin.service-zones.index'))->assertOk()
            ->assertSee('data-filters-for="'.ServiceZonesDataTable::TABLE_ID.'"', false);
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $category = $this->bookableCategory();
        $match = $this->zoneOffering([$category], ['name' => ['ar' => 'إبرة', 'en' => 'Needle zone'], 'status' => ZoneStatus::Draft, 'restricted_pickup' => true]);
        $other = ServiceZone::factory()->create();

        $url = route('admin.service-zones.index');
        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable($url, ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['city_ids' => [$other->city_id]])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $other->id);
        $this->datatable($url, ['statuses' => ['draft']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['category_ids' => [$category->id]])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['restricted_pickup' => '1'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();

        $this->precognitive('POST', route('admin.service-zones.store'), [])->assertStatus(422)->assertJsonValidationErrors(['city_id', 'name.ar', 'polygon']);
        $this->precognitive('POST', route('admin.service-zones.store'), $this->payload($city->id, ['polygon' => '[[30,31],[30.1,31.1]]']))->assertStatus(422)->assertJsonValidationErrors(['polygon']);
        $this->precognitive('POST', route('admin.service-zones.store'), $this->payload($city->id, ['operating_hours' => [['day' => 1, 'from' => '10:00', 'to' => '09:00']]]))->assertStatus(422);
        $this->precognitive('POST', route('admin.service-zones.store'), $this->payload($city->id))->assertNoContent();
        $this->assertDatabaseCount('service_zones', 0);
    }

    public function test_store_creates_a_draft_zone_with_its_polygon_and_audits_it(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();

        $this->postJson(route('admin.service-zones.store'), $this->payload($city->id, ['restricted_pickup' => '1']))->assertOk()->assertJsonPath('success', true);

        $zone = ServiceZone::query()->sole();
        $this->assertSame(ZoneStatus::Draft, $zone->status);
        $this->assertTrue($zone->restricted_pickup);
        $this->assertCount(4, $zone->polygon->vertices());
        $this->assertEquals([['day' => 1, 'from' => '06:00', 'to' => '23:00']], $zone->operating_hours);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'service_zone', 'auditable_id' => (string) $zone->id]);
    }

    public function test_a_self_intersecting_polygon_is_rejected_by_the_database_check(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();
        $bowtie = json_encode([[30.0, 31.2], [30.1, 31.3], [30.1, 31.2], [30.0, 31.3]]);

        $this->postJson(route('admin.service-zones.store'), $this->payload($city->id, ['polygon' => $bowtie]))
            ->assertStatus(422)->assertJsonPath('errors._code', 'zone_invalid_polygon');
        $this->assertDatabaseCount('service_zones', 0);
    }

    public function test_overlap_is_a_warning_in_the_response_and_the_live_endpoint(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $existing = ServiceZone::factory()->create();
        $overlapping = json_encode(ServiceZoneFactory::square(30.05, 31.25, 0.1)->toArray());
        $touching = json_encode(ServiceZoneFactory::square(30.0, 31.3, 0.1)->toArray());
        $apart = json_encode(ServiceZoneFactory::square(35.0, 35.0, 0.1)->toArray());

        $this->postJson(route('admin.service-zones.overlaps'), ['polygon' => $overlapping])->assertOk()->assertJsonPath('data.overlaps.0.id', $existing->id);
        $this->postJson(route('admin.service-zones.overlaps'), ['polygon' => $touching])->assertOk()->assertJsonCount(0, 'data.overlaps');
        $this->postJson(route('admin.service-zones.overlaps'), ['polygon' => $apart])->assertOk()->assertJsonCount(0, 'data.overlaps');
        $this->postJson(route('admin.service-zones.overlaps'), ['polygon' => $overlapping, 'zone_id' => $existing->id])->assertOk()->assertJsonCount(0, 'data.overlaps');

        $response = $this->postJson(route('admin.service-zones.store'), $this->payload($existing->city_id, ['polygon' => $overlapping]))->assertOk();
        $this->assertStringContainsString((string) $existing->name, (string) $response->json('message'));
        $this->assertDatabaseCount('service_zones', 2);
    }

    public function test_update_locks_versions_and_audits(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $zone = ServiceZone::factory()->create();
        $payload = $this->payload($zone->city_id, ['name' => ['ar' => 'اسم جديد', 'en' => 'New name']]);

        $this->putJson(route('admin.service-zones.update', $zone), $payload + ['lock_version' => 0])->assertOk();
        $this->assertSame(1, $zone->refresh()->lock_version);
        $this->assertSame('New name', $zone->getTranslation('name', 'en'));
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_id' => (string) $zone->id]);

        $this->putJson(route('admin.service-zones.update', $zone), $payload + ['lock_version' => 0])->assertStatus(409)->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_activation_needs_an_enabled_category_and_suspension_needs_a_reason(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $zone = ServiceZone::factory()->draft()->create();

        $this->postJson(route('admin.service-zones.activate', $zone))->assertStatus(422)->assertJsonPath('errors._code', 'zone_no_categories');

        ZoneServiceCategory::query()->create(['service_zone_id' => $zone->id, 'service_category_id' => $this->bookableCategory()->id, 'active' => true]);
        $this->postJson(route('admin.service-zones.activate', $zone))->assertOk();
        $this->assertSame(ZoneStatus::Active, $zone->refresh()->status);

        $this->postJson(route('admin.service-zones.suspend', $zone), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.service-zones.suspend', $zone), ['reason' => 'Road works'])->assertOk();
        $this->assertSame(ZoneStatus::Suspended, $zone->refresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $zone->id, 'reason' => 'Road works']);
    }

    public function test_an_active_zone_cannot_be_deleted_but_a_draft_can(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $active = ServiceZone::factory()->create();
        $draft = ServiceZone::factory()->draft()->create();

        $this->deleteJson(route('admin.service-zones.destroy', $active), ['reason' => 'cleanup please'])->assertForbidden();
        $this->deleteJson(route('admin.service-zones.destroy', $draft), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->deleteJson(route('admin.service-zones.destroy', $draft), ['reason' => 'cleanup please'])->assertOk();
        $this->assertModelMissing($draft);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $draft->id]);
    }

    public function test_availability_matrix_saves_overrides_and_audits(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $zone = ServiceZone::factory()->create();
        $a = $this->bookableCategory();
        $b = $this->bookableCategory();

        $payload = ['lock_version' => 0, 'categories' => [
            $a->id => ['active' => '1', 'min_fare_minor' => '2000', 'max_fare_minor' => '9000', 'dispatch_radius_steps_km' => '2, 4.5', 'hours_from' => '08:00', 'hours_to' => '20:00'],
            $b->id => ['active' => '0'],
        ]];

        $this->precognitive('PUT', route('admin.service-zones.availability', $zone), $payload)->assertNoContent();
        $this->putJson(route('admin.service-zones.availability', $zone), $payload)->assertOk();

        $link = ZoneServiceCategory::query()->where('service_zone_id', $zone->id)->where('service_category_id', $a->id)->sole();
        $this->assertTrue($link->active);
        $this->assertEquals(['min_fare_minor' => 2000, 'max_fare_minor' => 9000, 'dispatch_radius_steps_km' => [2.0, 4.5], 'hours' => ['from' => '08:00', 'to' => '20:00']], $link->overrides);
        $this->assertFalse(ZoneServiceCategory::query()->where('service_category_id', $b->id)->sole()->active);
        $this->assertSame(1, $zone->refresh()->lock_version);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_id' => (string) $zone->id]);

        $this->putJson(route('admin.service-zones.availability', $zone), $payload)->assertStatus(409);
    }

    public function test_availability_validation_and_active_zone_guard(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $category = $this->bookableCategory();
        $zone = $this->zoneOffering([$category]);

        $this->putJson(route('admin.service-zones.availability', $zone), ['lock_version' => 0, 'categories' => [$category->id => ['active' => '1', 'min_fare_minor' => '9000', 'max_fare_minor' => '2000']]])
            ->assertStatus(422)->assertJsonPath('errors._code', 'zone_override_fare_range');
        $this->putJson(route('admin.service-zones.availability', $zone), ['lock_version' => 0, 'categories' => [$category->id => ['active' => '1', 'dispatch_radius_steps_km' => '5, 2']]])
            ->assertStatus(422)->assertJsonPath('errors._code', 'zone_override_radius');
        $this->putJson(route('admin.service-zones.availability', $zone), ['lock_version' => 0, 'categories' => [$category->id => ['active' => '1', 'hours_from' => '10:00']]])
            ->assertStatus(422)->assertJsonValidationErrors(['categories.'.$category->id.'.hours_to']);
        $this->putJson(route('admin.service-zones.availability', $zone), ['lock_version' => 0, 'categories' => [99999 => ['active' => '1']]])
            ->assertStatus(422)->assertJsonPath('errors._code', 'zone_availability_invalid');
        $this->putJson(route('admin.service-zones.availability', $zone), ['lock_version' => 0, 'categories' => [$category->id => ['active' => '0']]])
            ->assertStatus(422)->assertJsonPath('errors._code', 'zone_no_categories');
    }

    public function test_show_edit_create_and_coverage_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $zone = $this->zoneOffering([$this->bookableCategory()]);

        $this->get(route('admin.service-zones.show', $zone))->assertOk()->assertSee('data-map-preview', false);
        $this->get(route('admin.service-zones.edit', $zone))->assertOk()->assertSee('data-map-polygon', false)->assertSee('data-zone-overlap', false);
        $this->get(route('admin.service-zones.create'))->assertOk();
        $this->get(route('admin.coverage-preview.show'))->assertOk();
    }

    public function test_coverage_preview_resolves_a_point_and_a_route(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $category = $this->bookableCategory();
        $this->zoneOffering([$category]);

        $inside = $this->postJson(route('admin.coverage-preview.run'), ['origin_lat' => 30.05, 'origin_lng' => 31.25])->assertOk();
        $this->assertStringContainsString($category->name, $inside->json('data.replace_html')['#coverage-result']);

        $outside = $this->postJson(route('admin.coverage-preview.run'), ['origin_lat' => 40.0, 'origin_lng' => 10.0])->assertOk();
        $this->assertStringContainsString(__('admin.coverage.outside'), $outside->json('data.replace_html')['#coverage-result']);

        $route = $this->postJson(route('admin.coverage-preview.run'), ['origin_lat' => 30.05, 'origin_lng' => 31.25, 'destination_lat' => 30.06, 'destination_lng' => 31.26])->assertOk();
        $this->assertStringContainsString('fake', $route->json('data.replace_html')['#coverage-result']);

        $this->precognitive('POST', route('admin.coverage-preview.run'), ['origin_lat' => 0, 'origin_lng' => 0])->assertStatus(422)->assertJsonValidationErrors(['origin_lat']);
        $this->precognitive('POST', route('admin.coverage-preview.run'), [])->assertStatus(422)->assertJsonValidationErrors(['origin_lat', 'origin_lng']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(int $cityId, array $overrides = []): array
    {
        return $overrides + [
            'city_id' => $cityId,
            'name' => ['ar' => 'وسط البلد', 'en' => 'Downtown'],
            'polygon' => json_encode(ServiceZoneFactory::square(30.0, 31.2, 0.1)->toArray()),
            'operating_hours' => [['day' => 1, 'from' => '06:00', 'to' => '23:00']],
            'restricted_pickup' => '0',
            'notes' => 'note',
        ];
    }
}
