<?php

namespace Tests\Feature\Admin\Catalog;

use App\DataTables\Admin\Catalog\ServiceFamiliesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Catalog\ServiceFamilyKind;
use App\Enums\Common\AuditAction;
use App\Models\AdminAuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Service Families.
 */
class ServiceFamilyAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.service-families.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $family = ServiceFamily::factory()->create();

        $this->get(route('admin.service-families.index'))->assertForbidden();
        $this->getJson(route('admin.service-families.show', $family))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.service-families.index'))->assertForbidden();
        $this->postJson(route('admin.service-families.store'), $this->payload())->assertForbidden();
        $this->postJson(route('admin.service-families.deactivate', $family), ['reason' => 'because reasons'])->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->get(route('admin.service-families.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.ServiceFamiliesDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.ServiceFamiliesDataTable::TABLE_ID.'"', false);
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $match = ServiceFamily::factory()->create(['name' => ['ar' => 'إبرة', 'en' => 'Needle family'], 'kind' => ServiceFamilyKind::Courier, 'active' => false]);
        ServiceFamily::factory()->create(['kind' => ServiceFamilyKind::Freight]);

        $this->datatable(route('admin.service-families.index'))->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable(route('admin.service-families.index'), ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.service-families.index'), ['kinds' => ['courier']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.service-families.index'), ['active' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.service-families.index'), ['active' => '1'])->assertJsonPath('recordsFiltered', 1);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->precognitive('POST', route('admin.service-families.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name.ar', 'kind']);
        $this->precognitive('POST', route('admin.service-families.store'), $this->payload(['code' => 'Bad Code']))
            ->assertStatus(422)->assertJsonValidationErrors(['code']);
        $this->precognitive('POST', route('admin.service-families.store'), $this->payload())->assertNoContent();
        $this->assertDatabaseCount('service_families', 0);
    }

    public function test_store_creates_an_inactive_family_and_audits_it(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->postJson(route('admin.service-families.store'), $this->payload())->assertOk()->assertJsonPath('success', true);

        $family = ServiceFamily::query()->where('code', 'rides_plus')->firstOrFail();
        $this->assertFalse($family->active);
        $this->assertSame('Rides plus', $family->getTranslation('name', 'en'));
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'service_family', 'auditable_id' => (string) $family->id]);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        ServiceFamily::factory()->create(['code' => 'rides_plus']);

        $this->postJson(route('admin.service-families.store'), $this->payload())->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_update_locks_versions_and_keeps_the_code_immutable(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $family = ServiceFamily::factory()->create(['code' => 'rides_plus']);
        $update = $this->payload(['name' => ['ar' => 'اسم جديد', 'en' => 'New name']]);
        unset($update['code']);

        $this->putJson(route('admin.service-families.update', $family), $update + ['lock_version' => 0])->assertOk();
        $family->refresh();
        $this->assertSame(1, $family->lock_version);
        $this->assertSame('rides_plus', $family->code);
        $this->assertTrue(AdminAuditLog::query()->where('action', AuditAction::Updated->value)->where('auditable_id', (string) $family->id)->exists());

        $this->putJson(route('admin.service-families.update', $family), $update + ['lock_version' => 0])
            ->assertStatus(409)->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_deactivation_needs_a_reason_and_audits_it(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        ServiceCategory::factory()->active()->create(); // keeps another family's category active elsewhere
        $family = ServiceFamily::factory()->create();

        $this->postJson(route('admin.service-families.deactivate', $family), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.service-families.deactivate', $family), ['reason' => 'Seasonal pause'])->assertOk();

        $this->assertFalse($family->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'auditable_id' => (string) $family->id, 'reason' => 'Seasonal pause']);

        $this->postJson(route('admin.service-families.activate', $family))->assertOk();
        $this->assertTrue($family->refresh()->active);
    }

    public function test_the_family_holding_the_last_active_categories_cannot_be_deactivated(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $family = ServiceFamily::factory()->create();
        ServiceCategory::factory()->active()->create(['service_family_id' => $family->id]);

        $this->postJson(route('admin.service-families.deactivate', $family), ['reason' => 'Seasonal pause'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'catalog_last_active_category');
        $this->assertTrue($family->refresh()->active);
    }

    public function test_a_family_with_categories_cannot_be_deleted(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $family = ServiceFamily::factory()->create();
        ServiceCategory::factory()->create(['service_family_id' => $family->id]);

        $this->deleteJson(route('admin.service-families.destroy', $family), ['reason' => 'cleanup please'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'family_has_categories');

        $empty = ServiceFamily::factory()->create();
        $this->deleteJson(route('admin.service-families.destroy', $empty), ['reason' => 'cleanup please'])
            ->assertOk()->assertJsonPath('data.reload_table', [ServiceFamiliesDataTable::TABLE_ID]);
        $this->assertModelMissing($empty);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $empty->id, 'reason' => 'cleanup please']);
    }

    public function test_show_create_and_edit_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $family = ServiceFamily::factory()->create();

        $this->get(route('admin.service-families.show', $family))->assertOk()->assertSee($family->code);
        $this->get(route('admin.service-families.create'))->assertOk()->assertSee('name="code"', false);
        $this->get(route('admin.service-families.edit', $family))->assertOk()->assertSee('name="lock_version"', false)->assertDontSee('name="code"', false);
        $this->get(route('admin.service-families.activity', $family))->assertOk();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return $override + [
            'code' => 'rides_plus',
            'name' => ['ar' => 'رحلات بلس', 'en' => 'Rides plus'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'kind' => 'passenger_city',
            'icon' => 'car',
            'sort_order' => 5,
        ];
    }
}
