<?php

namespace Tests\Feature\Admin\Catalog;

use App\DataTables\Admin\Catalog\ServiceCategoriesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Catalog\BookingType;
use App\Enums\Common\AuditAction;
use App\Models\ServiceCategory;
use App\Models\ServiceFamily;
use App\Models\VehicleCategory;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Service Categories: draft configuration, vehicle links and listing.
 */
class ServiceCategoryAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurrencySeeder::class);
    }

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.service-categories.index')));
    }

    public function test_admin_without_permission_gets_403_everywhere(): void
    {
        $this->actingAsAdmin();
        $category = ServiceCategory::factory()->create();

        $this->get(route('admin.service-categories.index'))->assertForbidden();
        $this->getJson(route('admin.service-categories.show', $category))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.service-categories.index'))->assertForbidden();
        $this->postJson(route('admin.service-categories.store'), $this->payload())->assertForbidden();
        $this->putJson(route('admin.service-categories.form-schema.update', $category), ['lock_version' => 0])->assertForbidden();
        $this->putJson(route('admin.service-categories.vehicle-categories.update', $category), [])->assertForbidden();
        $this->postJson(route('admin.service-categories.publish', $category), ['reason' => 'ready to go'])->assertForbidden();
        $this->postJson(route('admin.service-categories.deactivate', $category), ['reason' => 'ready to go'])->assertForbidden();
        $this->getJson(route('admin.service-categories.versions', $category))->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->get(route('admin.service-categories.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.ServiceCategoriesDataTable::TABLE_ID.'"', false)
            ->assertSee('id="'.ServiceCategoriesDataTable::TABLE_ID.'"', false);
    }

    public function test_datatable_json_respects_each_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $family = ServiceFamily::factory()->create();
        $match = ServiceCategory::factory()->create([
            'service_family_id' => $family->id,
            'name' => ['ar' => 'إبرة', 'en' => 'Needle category'],
            'allowed_booking_types' => [BookingType::Parcel->value],
            'negotiation_enabled' => false,
        ]);
        ServiceCategory::factory()->active()->create();

        $url = route('admin.service-categories.index');
        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable($url, ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['family' => $family->id])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['active' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['booking_type' => 'parcel'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['booking_type' => 'on_demand'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['negotiation_enabled' => '0'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->precognitive('POST', route('admin.service-categories.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['service_family_id', 'code', 'name.ar', 'allowed_booking_types', 'payment_methods']);

        $this->precognitive('POST', route('admin.service-categories.store'), $this->payload([
            'min_customer_fare_minor' => 5000,
            'max_customer_fare_minor' => 1000,
            'counter_offer_min_pct' => 150,
            'dispatch_radius_steps_km' => '7, 4',
            'service_hours' => [['day' => 1, 'from' => '10:00', 'to' => '09:00']],
            'supports_stops' => '1',
        ]))->assertStatus(422)->assertJsonValidationErrors([
            'max_customer_fare_minor', 'counter_offer_min_pct', 'dispatch_radius_steps_km', 'service_hours.0.to', 'max_stops',
        ]);

        $this->precognitive('POST', route('admin.service-categories.store'), $this->payload(['cash_allowed' => '1', 'payment_methods' => ['card']]))
            ->assertStatus(422)->assertJsonValidationErrors(['payment_methods']);

        $this->precognitive('POST', route('admin.service-categories.store'), $this->payload())->assertNoContent();
        $this->assertDatabaseCount('service_categories', 0);
    }

    public function test_store_creates_an_inactive_unpublished_category_with_an_empty_draft_schema(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);

        $this->postJson(route('admin.service-categories.store'), $this->payload())->assertOk()->assertJsonPath('success', true);

        $category = ServiceCategory::query()->where('code', 'ride_plus')->firstOrFail();
        $this->assertFalse($category->active);
        $this->assertNull($category->published_version_id);
        $this->assertSame(['on_demand', 'scheduled'], $category->allowed_booking_types);
        $this->assertSame(1500, $category->min_customer_fare_minor);
        $this->assertEquals([2.0, 4.0, 7.0], $category->dispatch_radius_steps_km);
        $this->assertEquals([['day' => 1, 'from' => '08:00', 'to' => '22:00']], $category->service_hours);
        $this->assertNull($category->pin_required);
        $this->assertSame('draft', $category->draftSchema()->firstOrFail()->status->value);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'service_category', 'auditable_id' => (string) $category->id]);
    }

    public function test_update_changes_the_draft_with_optimistic_locking_and_immutable_code(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = ServiceCategory::factory()->create(['code' => 'ride_plus']);
        $update = $this->payload(['max_passengers' => 6, 'pin_required' => '0']);
        unset($update['code']);

        $this->putJson(route('admin.service-categories.update', $category), $update + ['lock_version' => 0])->assertOk();
        $category->refresh();
        $this->assertSame(6, $category->max_passengers);
        $this->assertFalse($category->pin_required);
        $this->assertSame(1, $category->lock_version);
        $this->assertSame('ride_plus', $category->code);

        $this->putJson(route('admin.service-categories.update', $category), $update + ['lock_version' => 0])
            ->assertStatus(409)->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_vehicle_categories_can_be_linked_and_the_change_is_audited(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = ServiceCategory::factory()->create();
        [$a, $b] = VehicleCategory::factory()->count(2)->create();

        $this->putJson(route('admin.service-categories.vehicle-categories.update', $category), ['vehicle_category_ids' => [$a->id, $b->id]])->assertOk();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $category->vehicleCategories()->pluck('vehicle_categories.id')->all());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_id' => (string) $category->id]);

        $this->putJson(route('admin.service-categories.vehicle-categories.update', $category), ['vehicle_category_ids' => [$a->id, 9999]])
            ->assertStatus(422)->assertJsonValidationErrors(['vehicle_category_ids.1']);

        $this->putJson(route('admin.service-categories.vehicle-categories.update', $category), ['vehicle_category_ids' => [$a->id]])->assertOk();
        $this->assertSame([$a->id], $category->vehicleCategories()->pluck('vehicle_categories.id')->all());
    }

    public function test_a_category_that_was_never_published_can_be_deleted_only_when_inactive(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $active = ServiceCategory::factory()->active()->create();
        $draft = ServiceCategory::factory()->create();

        $this->deleteJson(route('admin.service-categories.destroy', $active), ['reason' => 'cleanup please'])
            ->assertStatus(422)->assertJsonPath('errors._code', 'category_not_deletable');
        $this->deleteJson(route('admin.service-categories.destroy', $draft), ['reason' => 'cleanup please'])
            ->assertOk()->assertJsonPath('data.reload_table', [ServiceCategoriesDataTable::TABLE_ID]);
        $this->assertModelMissing($draft);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $draft->id, 'reason' => 'cleanup please']);
    }

    public function test_show_create_edit_versions_and_activity_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::ServicesManage]);
        $category = ServiceCategory::factory()->create();

        $this->get(route('admin.service-categories.show', $category))->assertOk()->assertSee($category->code)->assertSee('name="lock_version"', false);
        $this->get(route('admin.service-categories.create'))->assertOk()->assertSee('name="allowed_booking_types[]"', false)->assertSee('data-repeater-row', false);
        $this->get(route('admin.service-categories.edit', $category))->assertOk()->assertSee('name="lock_version"', false)->assertDontSee('name="code"', false);
        $this->get(route('admin.service-categories.versions', $category))->assertOk();
        $this->get(route('admin.service-categories.activity', $category))->assertOk();
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return $override + [
            'service_family_id' => ServiceFamily::query()->first()?->id ?? ServiceFamily::factory()->create()->id,
            'code' => 'ride_plus',
            'name' => ['ar' => 'رحلة بلس', 'en' => 'Ride plus'],
            'description' => ['ar' => 'وصف', 'en' => 'Description'],
            'icon' => 'car',
            'sort_order' => 1,
            'allowed_booking_types' => ['on_demand', 'scheduled'],
            'supports_stops' => '0',
            'service_hours' => [['day' => 1, 'from' => '08:00', 'to' => '22:00']],
            'negotiation_enabled' => '1',
            'reference_estimate_enabled' => '1',
            'min_customer_fare_minor' => '1500',
            'max_customer_fare_minor' => '100000',
            'counter_offer_min_pct' => '70',
            'counter_offer_max_pct' => '200',
            'dispatch_radius_steps_km' => '2, 4, 7',
            'max_passengers' => 4,
            'pin_required' => '',
            'pickup_proof_required' => '0',
            'recipient_otp_required' => '0',
            'tipping_enabled' => '1',
            'coupon_eligible' => '1',
            'cash_allowed' => '1',
            'payment_methods' => ['cash', 'card'],
        ];
    }
}
