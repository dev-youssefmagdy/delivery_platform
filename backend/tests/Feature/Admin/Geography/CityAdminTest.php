<?php

namespace Tests\Feature\Admin\Geography;

use App\DataTables\Admin\Geography\CitiesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Models\City;
use App\Models\Country;
use App\Models\ServiceZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Cities.
 */
class CityAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.cities.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $city = City::factory()->create();

        $this->get(route('admin.cities.index'))->assertForbidden();
        $this->getJson(route('admin.cities.show', $city))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.cities.index'))->assertForbidden();
        $this->postJson(route('admin.cities.store'), $this->payload())->assertForbidden();
        $this->postJson(route('admin.cities.deactivate', $city), ['reason' => 'because reasons'])->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);

        $this->get(route('admin.cities.index'))->assertOk()
            ->assertSee('data-filters-for="'.CitiesDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.CitiesDataTable::TABLE_ID.'"', false);
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $match = City::factory()->inactive()->create(['name' => ['ar' => 'إبرة', 'en' => 'Needle town']]);
        City::factory()->create();

        $this->datatable(route('admin.cities.index'))->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable(route('admin.cities.index'), ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.cities.index'), ['active' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.cities.index'), ['country_ids' => [$match->country_id]])->assertJsonPath('recordsFiltered', 1);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);

        $this->precognitive('POST', route('admin.cities.store'), [])->assertStatus(422)
            ->assertJsonValidationErrors(['country_id', 'name.ar', 'timezone', 'center_lat', 'center_lng']);
        $this->precognitive('POST', route('admin.cities.store'), $this->payload(['timezone' => 'Mars/Base']))->assertStatus(422)->assertJsonValidationErrors(['timezone']);
        $this->precognitive('POST', route('admin.cities.store'), $this->payload(['center_lat' => 0, 'center_lng' => 0]))->assertStatus(422)->assertJsonValidationErrors(['center_lat']);
        $this->precognitive('POST', route('admin.cities.store'), $this->payload())->assertNoContent();
        $this->assertDatabaseCount('cities', 0);
    }

    public function test_store_creates_an_inactive_city_with_its_center_and_audits_it(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);

        $this->postJson(route('admin.cities.store'), $this->payload())->assertOk()->assertJsonPath('success', true);

        $city = City::query()->sole();
        $this->assertFalse($city->active);
        $this->assertEqualsWithDelta(30.0444, $city->center->lat, 1e-6);
        $this->assertEqualsWithDelta(31.2357, $city->center->lng, 1e-6);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'city', 'auditable_id' => (string) $city->id]);
    }

    public function test_inactive_country_is_rejected(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $country = Country::factory()->create(['active' => false]);

        $this->postJson(route('admin.cities.store'), $this->payload(['country_id' => $country->id]))
            ->assertStatus(422)->assertJsonPath('errors._code', 'city_country_inactive');
    }

    public function test_update_locks_versions_and_audits(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();
        $payload = $this->payload(['country_id' => $city->country_id, 'center_lat' => 31.2, 'center_lng' => 29.9]);

        $this->putJson(route('admin.cities.update', $city), $payload + ['lock_version' => 0])->assertOk();
        $city->refresh();
        $this->assertSame(1, $city->lock_version);
        $this->assertEqualsWithDelta(31.2, $city->center->lat, 1e-6);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_id' => (string) $city->id]);

        $this->putJson(route('admin.cities.update', $city), $payload + ['lock_version' => 0])->assertStatus(409)->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_deactivation_needs_a_reason_and_audits_it(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();

        $this->postJson(route('admin.cities.deactivate', $city), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.cities.deactivate', $city), ['reason' => 'Seasonal pause'])->assertOk();
        $this->assertFalse($city->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $city->id, 'reason' => 'Seasonal pause']);

        $this->postJson(route('admin.cities.activate', $city))->assertOk();
        $this->assertTrue($city->refresh()->active);
    }

    public function test_a_city_with_zones_cannot_be_deleted(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();
        ServiceZone::factory()->create(['city_id' => $city->id]);

        $this->deleteJson(route('admin.cities.destroy', $city), ['reason' => 'cleanup please'])->assertStatus(422)->assertJsonPath('errors._code', 'city_has_zones');

        $empty = City::factory()->create();
        $this->deleteJson(route('admin.cities.destroy', $empty))->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->deleteJson(route('admin.cities.destroy', $empty), ['reason' => 'cleanup please'])->assertOk();
        $this->assertModelMissing($empty);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $empty->id]);
    }

    public function test_show_page_renders(): void
    {
        $this->actingAsAdmin([AdminPermission::ServiceAreasManage]);
        $city = City::factory()->create();

        $this->get(route('admin.cities.show', $city))->assertOk()->assertSee($city->timezone);
        $this->get(route('admin.cities.edit', $city))->assertOk();
        $this->get(route('admin.cities.create'))->assertOk();
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'country_id' => Country::factory()->create()->id,
            'name' => ['ar' => 'القاهرة', 'en' => 'Cairo'],
            'timezone' => 'Africa/Cairo',
            'center_lat' => 30.0444,
            'center_lng' => 31.2357,
        ];
    }
}
