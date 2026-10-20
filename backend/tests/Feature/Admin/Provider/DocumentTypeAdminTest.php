<?php

namespace Tests\Feature\Admin\Provider;

use App\DataTables\Admin\Provider\DocumentTypesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentSubject;
use App\Enums\Provider\ProviderType;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Document types and their requirements.
 */
class DocumentTypeAdminTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'code' => 'driving_licence', 'name' => ['ar' => 'رخصة القيادة', 'en' => 'Driving licence'],
            'applies_to' => 'provider', 'requires_expiry' => '1', 'requires_number' => '1',
            'allowed_mimes' => ['image/jpeg', 'application/pdf'], 'max_size_kb' => 4096, 'sort_order' => 10,
        ];
    }

    public function test_guests_are_blocked_and_other_permissions_are_not_enough(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.document-types.index')));

        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DocumentsView, AdminPermission::ServicesManage]);
        $type = DocumentType::factory()->create();

        $this->get(route('admin.document-types.index'))->assertForbidden();
        $this->getJson(route('admin.document-types.show', $type))->assertForbidden();
        $this->datatable(route('admin.document-types.index'))->assertForbidden();
        $this->postJson(route('admin.document-types.store'), $this->payload())->assertForbidden();
        $this->postJson(route('admin.document-types.requirements.store', $type), ['reason' => 'Because of the law'])->assertForbidden();
    }

    public function test_index_has_a_collapsed_filters_card_and_filters_work(): void
    {
        $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);
        $match = DocumentType::factory()->forVehicle()->create(['name' => ['ar' => 'تأمين', 'en' => 'Insurance papers']]);
        DocumentType::factory()->create(['active' => false]);

        $this->get(route('admin.document-types.index'))->assertOk()->assertSee('data-filters-for="'.DocumentTypesDataTable::TABLE_ID.'"', false);
        $this->datatable(route('admin.document-types.index'), ['search' => 'Insurance'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable(route('admin.document-types.index'), ['applies_to' => ['vehicle']])->assertJsonPath('recordsFiltered', 1);
        $this->datatable(route('admin.document-types.index'), ['active' => '0'])->assertJsonPath('recordsFiltered', 1);
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);
        $url = route('admin.document-types.store');

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['code', 'name.ar', 'applies_to', 'allowed_mimes', 'max_size_kb']);
        $this->precognitive('POST', $url, $this->payload(['allowed_mimes' => ['application/zip'], 'max_size_kb' => 999999]))->assertStatus(422)->assertJsonValidationErrors(['allowed_mimes.0', 'max_size_kb']);
        $this->precognitive('POST', $url, $this->payload())->assertNoContent();
        $this->assertDatabaseCount('document_types', 0);
    }

    public function test_store_update_activate_deactivate_delete_and_audit(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);

        $this->postJson(route('admin.document-types.store'), $this->payload())->assertOk();
        $type = DocumentType::query()->where('code', 'driving_licence')->firstOrFail();
        $this->assertTrue($type->active);
        $this->assertTrue($type->requires_expiry);
        $this->assertSame(DocumentSubject::Provider, $type->applies_to);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'document_type', 'actor_id' => $admin->id]);
        $this->postJson(route('admin.document-types.store'), $this->payload())->assertStatus(422)->assertJsonValidationErrors(['code']);

        $update = $this->payload(['max_size_kb' => 2048, 'requires_expiry' => '0']);
        unset($update['code']);
        $this->putJson(route('admin.document-types.update', $type), $update + ['lock_version' => 0])->assertOk();
        $type->refresh();
        $this->assertSame(2048, $type->max_size_kb);
        $this->assertFalse($type->requires_expiry);
        $this->putJson(route('admin.document-types.update', $type), $update + ['lock_version' => 0])->assertStatus(409);

        $this->postJson(route('admin.document-types.deactivate', $type), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.document-types.deactivate', $type), ['reason' => 'Replaced by the new licence type'])->assertOk();
        $this->assertFalse($type->refresh()->active);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::StatusChanged->value, 'reason' => 'Replaced by the new licence type']);
        $this->postJson(route('admin.document-types.activate', $type))->assertOk();

        $this->deleteJson(route('admin.document-types.destroy', $type), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->deleteJson(route('admin.document-types.destroy', $type), ['reason' => 'Created by mistake'])->assertOk();
        $this->assertModelMissing($type);
    }

    public function test_a_type_with_uploads_cannot_be_deleted_or_change_subject(): void
    {
        $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);
        $type = DocumentType::factory()->create();
        $this->providerDocument($this->makeProvider(), $type);

        $this->deleteJson(route('admin.document-types.destroy', $type), ['reason' => 'Clean up unused types'])->assertStatus(422)->assertJsonPath('errors._code', 'document_type_in_use');

        $payload = $this->payload(['applies_to' => 'vehicle']);
        unset($payload['code']);
        $this->putJson(route('admin.document-types.update', $type), $payload + ['lock_version' => 0])->assertStatus(422)->assertJsonPath('errors._code', 'document_type_subject_locked');
    }

    public function test_requirements_are_added_and_removed_with_a_reason_and_audit(): void
    {
        $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);
        $type = DocumentType::factory()->create();
        $city = $this->providerCity();
        $url = route('admin.document-types.requirements.store', $type);
        $body = ['country_id' => $city->country_id, 'city_id' => '', 'provider_type' => 'company_fleet', 'mandatory' => '1', 'reason' => 'Required by the local transport authority'];

        $this->precognitive('POST', $url, ['reason' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->precognitive('POST', $url, array_merge($body, ['country_id' => 999999]))->assertStatus(422)->assertJsonValidationErrors(['country_id']);
        $this->precognitive('POST', $url, $body)->assertNoContent();
        $this->assertDatabaseCount('document_requirements', 0);

        $this->postJson($url, array_diff_key($body, ['reason' => 1]))->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, $body)->assertOk();
        $requirement = DocumentRequirement::query()->firstOrFail();
        $this->assertSame($city->country_id, $requirement->country_id);
        $this->assertNull($requirement->city_id);
        $this->assertSame(ProviderType::CompanyFleet, $requirement->provider_type);
        $this->assertTrue($requirement->mandatory);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Created->value, 'auditable_type' => 'document_requirement', 'reason' => 'Required by the local transport authority']);

        $this->datatable(route('admin.document-types.requirements', $type))->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.provider_type', ProviderType::CompanyFleet->label());

        $this->deleteJson(route('admin.document-types.requirements.destroy', [$type, $requirement->id]), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->deleteJson(route('admin.document-types.requirements.destroy', [$type, $requirement->id]), ['reason' => 'No longer required there'])->assertOk();
        $this->assertDatabaseCount('document_requirements', 0);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_type' => 'document_requirement', 'reason' => 'No longer required there']);
    }

    public function test_a_requirement_cannot_be_removed_through_another_type(): void
    {
        $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);
        $one = DocumentType::factory()->create();
        $two = DocumentType::factory()->create();
        $requirement = DocumentRequirement::factory()->create(['document_type_id' => $one->id]);

        $this->deleteJson(route('admin.document-types.requirements.destroy', [$two, $requirement->id]), ['reason' => 'Trying another type'])->assertNotFound();
        $this->assertModelExists($requirement);
    }

    public function test_show_pages_render(): void
    {
        $this->actingAsAdmin([AdminPermission::DocumentsManageTypes]);
        $type = DocumentType::factory()->create();

        $this->get(route('admin.document-types.create'))->assertOk();
        $this->get(route('admin.document-types.show', $type))->assertOk()->assertSee($type->code);
        $this->get(route('admin.document-types.edit', $type))->assertOk();
        $this->get(route('admin.document-types.requirements', $type))->assertOk()->assertSee('document-requirement-add', false);
    }
}
