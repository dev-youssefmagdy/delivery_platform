<?php

namespace Tests\Feature\Admin\Provider;

use App\DataTables\Admin\Provider\VehiclesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\VehicleStatus;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for the Vehicles listing and the approve / reject decisions.
 */
class VehicleAdminTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_blocked_and_vehicles_view_is_required(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.vehicles.index')));

        $vehicle = $this->vehicleFor($this->makeProvider());
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DocumentsView]);

        $this->get(route('admin.vehicles.index'))->assertForbidden();
        $this->datatable(route('admin.vehicles.index'))->assertForbidden();
        $this->getJson(route('admin.vehicles.show', $vehicle))->assertForbidden();
        $this->getJson(route('admin.vehicles.tabs.documents', $vehicle))->assertForbidden();
    }

    public function test_index_has_a_collapsed_filters_card_and_every_filter_works(): void
    {
        $this->actingAsAdmin([AdminPermission::VehiclesView]);
        $category = VehicleCategory::factory()->create();
        $provider = $this->makeProvider();
        $match = $this->vehicleFor($provider, VehicleStatus::UnderReview, ['plate_display' => 'أ ب ج 1234', 'plate_normalized' => 'أبج1234', 'vehicle_category_id' => $category->id]);
        $this->vehicleFor($this->makeProvider(), VehicleStatus::Approved);

        $this->get(route('admin.vehicles.index'))->assertOk()->assertSee('data-filters-for="'.VehiclesDataTable::TABLE_ID.'"', false);
        $check = fn (array $filters, int $count) => $this->datatable(route('admin.vehicles.index'), $filters)->assertJsonPath('recordsFiltered', $count);

        $check([], 2);
        $check(['plate' => 'ا ب ج-1234'], 0);
        $check(['plate' => 'أبج'], 1);
        $check(['plate' => '١٢٣٤'], 1);
        $check(['vehicle_category_ids' => [$category->id]], 1);
        $check(['statuses' => ['under_review']], 1);
        $check(['statuses' => ['rejected']], 0);
        $check(['provider_ids' => [$provider->id]], 1);
        $this->assertSame($match->id, $this->datatable(route('admin.vehicles.index'), ['provider_ids' => [$provider->id]])->json('data.0.id'));
    }

    public function test_approve_needs_the_vehicle_documents_approved_and_is_audited(): void
    {
        $registration = $this->vehicleDocumentType(['code' => 'registration']);
        $provider = $this->makeProvider(ProviderStatus::Approved);
        $vehicle = $this->vehicleFor($provider, VehicleStatus::UnderReview);
        $document = $this->documentOfVehicle($vehicle, $registration);
        $officer = $this->actingAsAdmin([AdminPermission::VehiclesView, AdminPermission::VehiclesApprove, AdminPermission::VehiclesReject]);

        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertStatus(422)->assertJsonPath('errors._code', 'vehicle_documents_incomplete');

        $this->postJson(route('admin.vehicle-documents.approve', $document->uuid))->assertOk();
        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertOk();

        $vehicle->refresh();
        $this->assertSame([VehicleStatus::Approved, $officer->id], [$vehicle->status, $vehicle->reviewed_by_admin_id]);
        $this->assertSame($vehicle->id, $provider->refresh()->current_vehicle_id);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Approved->value, 'auditable_type' => 'vehicle', 'auditable_id' => (string) $vehicle->id, 'actor_id' => $officer->id]);
        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertForbidden();
    }

    public function test_reject_needs_a_reason_and_the_provider_sees_it(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider(ProviderStatus::DocumentsPending), VehicleStatus::UnderReview);
        $this->actingAsAdmin([AdminPermission::VehiclesView, AdminPermission::VehiclesReject]);
        $url = route('admin.vehicles.reject', $vehicle);

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, ['reason' => 'The plate is not visible'])->assertOk();

        $this->assertSame([VehicleStatus::Rejected, 'The plate is not visible'], [$vehicle->refresh()->status, $vehicle->rejection_reason]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Rejected->value, 'auditable_type' => 'vehicle', 'reason' => 'The plate is not visible']);

        $this->actingAsProvider($vehicle->providerProfile);
        $this->getJson("/api/v1/provider/vehicles/{$vehicle->uuid}")->assertOk()->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.rejection_reason', 'The plate is not visible')->assertJsonPath('data.editable', true);
    }

    public function test_a_read_only_role_has_no_buttons_and_gets_403(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider(), VehicleStatus::UnderReview);
        $this->actingAsAdmin([AdminPermission::VehiclesView]);

        $this->get(route('admin.vehicles.show', $vehicle))->assertOk()->assertDontSee(route('admin.vehicles.approve', $vehicle));
        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertForbidden();
        $this->postJson(route('admin.vehicles.reject', $vehicle), ['reason' => 'Some good reason'])->assertForbidden();
        $this->assertSame(VehicleStatus::UnderReview, $vehicle->refresh()->status);
    }

    public function test_approve_and_reject_need_distinct_permissions(): void
    {
        $vehicle = $this->vehicleFor($this->makeProvider(), VehicleStatus::UnderReview);
        $this->actingAsAdmin([AdminPermission::VehiclesView, AdminPermission::VehiclesApprove]);
        $this->postJson(route('admin.vehicles.reject', $vehicle), ['reason' => 'No reject permission'])->assertForbidden();

        $this->actingAsAdmin([AdminPermission::VehiclesView, AdminPermission::VehiclesReject]);
        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertForbidden();
    }

    public function test_the_show_page_and_its_tabs_render_with_photos_and_documents(): void
    {
        $this->fakePrivateDisk();
        $vehicle = $this->vehicleFor($this->makeProvider(), VehicleStatus::UnderReview);
        $this->documentOfVehicle($vehicle, $this->vehicleDocumentType(['code' => 'registration']), DocumentStatus::Pending);
        $vehicle->photos()->create(['kind' => 'front', 'path' => 'x/front.jpg', 'mime' => 'image/jpeg', 'size_bytes' => 10, 'file_hash' => str_repeat('a', 64)]);
        $this->actingAsAdmin([AdminPermission::VehiclesView, AdminPermission::DocumentsView]);

        $this->get(route('admin.vehicles.show', $vehicle))->assertOk()->assertSee($vehicle->plate_display)->assertSee('data-document-viewer', false);
        $this->datatable(route('admin.vehicles.tabs.documents', $vehicle))->assertJsonPath('recordsTotal', 1);
        $this->get(route('admin.vehicles.tabs.audit', $vehicle))->assertOk();
    }
}
