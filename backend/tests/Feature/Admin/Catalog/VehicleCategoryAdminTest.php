<?php

namespace Tests\Feature\Admin\Catalog;

use App\DataTables\Admin\Catalog\VehicleCategoriesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Models\ServiceCategory;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Vehicle Categories.
 */
class VehicleCategoryAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.vehicle-categories.index')));
    }

    public function test_services_manage_alone_is_not_enough(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $vehicle = VehicleCategory::factory()->create();

        $this->get(route('admin.vehicle-categories.index'))->assertForbidden();
        $this->getJson(route('admin.vehicle-categories.show', $vehicle))->assertForbidden();
        $this->datatable(route('admin.vehicle-categories.index'))->assertForbidden();
        $this->postJson(route('admin.vehicle-categories.store'), $this->payload())->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card_and_filters_work(): void
    {
        $this->actingAsAdmin([AdminPermission::VehicleCategoriesManage]);
        $match = VehicleCategory::factory()->create(['name' => ['ar' => 'إبرة', 'en' => 'Needle van'], 'active' => false]);
        VehicleCategory::factory()->create();

        $this->get(route('admin.vehicle-categories.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.VehicleCategoriesDataTable::TABLE_ID.'"', false);
        $this->datatable(route('admin.vehicle-categories.index'), ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.vehicle-categories.index'), ['active' => '0'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable(route('admin.vehicle-categories.index'), ['active' => '1'])->assertJsonPath('recordsFiltered', 1);
    }

    public function test_precognitive_store_validates_capacities_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::VehicleCategoriesManage]);

        $this->precognitive('POST', route('admin.vehicle-categories.store'), [])->assertStatus(422)->assertJsonValidationErrors(['code', 'name.ar']);
        $this->precognitive('POST', route('admin.vehicle-categories.store'), $this->payload(['passenger_capacity' => 0, 'max_payload_kg' => -5]))
            ->assertStatus(422)->assertJsonValidationErrors(['passenger_capacity', 'max_payload_kg']);
        $this->precognitive('POST', route('admin.vehicle-categories.store'), $this->payload())->assertNoContent();
        $this->assertDatabaseCount('vehicle_categories', 0);
    }

    public function test_store_update_activate_deactivate_and_audit(): void
    {
        $this->actingAsAdmin([AdminPermission::VehicleCategoriesManage]);

        $this->postJson(route('admin.vehicle-categories.store'), $this->payload())->assertOk();
        $vehicle = VehicleCategory::query()->where('code', 'van_7')->firstOrFail();
        $this->assertFalse($vehicle->active);
        $this->assertSame(7, $vehicle->passenger_capacity);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'vehicle_category']);

        $update = $this->payload(['passenger_capacity' => 6]);
        unset($update['code']);
        $this->putJson(route('admin.vehicle-categories.update', $vehicle), $update + ['lock_version' => 0])->assertOk();
        $this->assertSame(6, $vehicle->refresh()->passenger_capacity);
        $this->putJson(route('admin.vehicle-categories.update', $vehicle), $update + ['lock_version' => 0])->assertStatus(409);

        $this->postJson(route('admin.vehicle-categories.activate', $vehicle))->assertOk();
        $this->assertTrue($vehicle->refresh()->active);
        $this->postJson(route('admin.vehicle-categories.deactivate', $vehicle), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.vehicle-categories.deactivate', $vehicle), ['reason' => 'Retired class'])->assertOk();
        $this->assertFalse($vehicle->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $vehicle->id, 'reason' => 'Retired class']);
    }

    public function test_a_linked_vehicle_category_cannot_be_deleted(): void
    {
        $this->actingAsAdmin([AdminPermission::VehicleCategoriesManage]);
        $vehicle = VehicleCategory::factory()->create();
        ServiceCategory::factory()->create()->vehicleCategories()->attach($vehicle);

        $this->deleteJson(route('admin.vehicle-categories.destroy', $vehicle), ['reason' => 'cleanup please'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_category_in_use');

        $free = VehicleCategory::factory()->create();
        $this->deleteJson(route('admin.vehicle-categories.destroy', $free), ['reason' => 'cleanup please'])->assertOk();
        $this->assertModelMissing($free);
    }

    public function test_show_create_edit_and_activity_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::VehicleCategoriesManage]);
        $vehicle = VehicleCategory::factory()->create();

        $this->get(route('admin.vehicle-categories.show', $vehicle))->assertOk()->assertSee($vehicle->code);
        $this->get(route('admin.vehicle-categories.create'))->assertOk()->assertSee('name="passenger_capacity"', false);
        $this->get(route('admin.vehicle-categories.edit', $vehicle))->assertOk()->assertSee('name="lock_version"', false);
        $this->get(route('admin.vehicle-categories.activity', $vehicle))->assertOk();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return $override + [
            'code' => 'van_7',
            'name' => ['ar' => 'ميكروباص', 'en' => 'Minivan'],
            'icon' => 'van',
            'map_marker' => 'van',
            'passenger_capacity' => 7,
            'luggage_capacity' => 4,
            'sort_order' => 3,
        ];
    }
}
