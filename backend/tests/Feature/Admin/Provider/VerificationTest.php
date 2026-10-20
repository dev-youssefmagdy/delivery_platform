<?php

namespace Tests\Feature\Admin\Provider;

use App\DataTables\Admin\Provider\VerificationQueueDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Enums\Provider\VehicleStatus;
use App\Enums\Settings\SettingKey;
use App\Models\AdminAuditLog;
use App\Models\ProviderProfile;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Verification queue and review screen: listing with SLA age, claiming, approve / request more information / reject
 * with the required reasons, permissions and audit.
 */
class VerificationTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    private const OFFICER = [AdminPermission::DriversView, AdminPermission::DriversVerify, AdminPermission::DriversReject, AdminPermission::DocumentsView, AdminPermission::VehiclesView, AdminPermission::VehiclesApprove, AdminPermission::VehiclesReject];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePrivateDisk();
    }

    /**
     * An application under review with one provider document (pending) and one vehicle with a pending document.
     *
     * @return array{provider: ProviderProfile, licence: \App\Models\ProviderDocument, category: \App\Models\ServiceCategory, vehicle: \App\Models\Vehicle, registration: \App\Models\VehicleDocument}
     */
    private function application(ProviderStatus $status = ProviderStatus::UnderReview): array
    {
        $category = $this->bookableCategory();
        $licenceType = $this->documentType(['code' => 'licence', 'requires_expiry' => true]);
        $registrationType = $this->vehicleDocumentType(['code' => 'registration']);
        $provider = $this->makeProvider($status);
        $this->requestService($provider, $category);
        $licence = $this->providerDocument($provider, $licenceType, DocumentStatus::Pending, now()->addYear()->toDateString());
        $vehicle = $this->vehicleFor($provider, VehicleStatus::UnderReview, ['vehicle_category_id' => $category->vehicleCategories()->firstOrFail()->id]);
        $registration = $this->documentOfVehicle($vehicle, $registrationType);

        return compact('provider', 'licence', 'category', 'vehicle', 'registration');
    }

    public function test_guests_are_blocked_and_the_queue_needs_drivers_view(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.verification.index')));

        ['provider' => $provider] = $this->application();
        $this->actingAsAdmin([AdminPermission::VehiclesView, AdminPermission::DocumentsView]);

        $this->get(route('admin.verification.index'))->assertForbidden();
        $this->datatable(route('admin.verification.index'))->assertForbidden();
        $this->get(route('admin.verification.review', $provider))->assertForbidden();
        $this->postJson(route('admin.verification.approve', $provider), ['approved_services' => [1]])->assertForbidden();
    }

    public function test_the_queue_lists_only_applications_awaiting_review_oldest_first_with_sla_age(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView]);
        app(SettingsService::class)->set(SettingKey::ProviderVerificationSlaHours, 24);
        $old = $this->makeProvider(ProviderStatus::UnderReview, ['submitted_at' => now()->subHours(60)]);
        $fresh = $this->makeProvider(ProviderStatus::UnderReview, ['submitted_at' => now()->subHours(2)]);
        $info = $this->makeProvider(ProviderStatus::MoreInformationRequired, ['submitted_at' => now()->subHours(30)]);
        $this->makeProvider(ProviderStatus::Approved);
        $this->makeProvider(ProviderStatus::Rejected);

        $this->get(route('admin.verification.index'))->assertOk()->assertSee('data-filters-for="'.VerificationQueueDataTable::TABLE_ID.'"', false);
        $response = $this->datatable(route('admin.verification.index'))->assertJsonPath('recordsTotal', 3);

        $this->assertSame([$old->id, $info->id, $fresh->id], array_column($response->json('data'), 'id'));
        $this->assertStringContainsString('badge-danger', $response->json('data.0.waiting'), '60h is more than twice the 24h SLA');
        $this->assertStringContainsString('badge-warning', $response->json('data.1.waiting'));
        $this->assertStringNotContainsString('badge-warning', $response->json('data.2.waiting'));
    }

    public function test_every_queue_filter_works(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView]);
        $officer = $this->actingAsAdmin([AdminPermission::DriversView]);
        $city = $this->providerCity();
        $match = $this->makeProvider(ProviderStatus::MoreInformationRequired, ['submitted_at' => now()->subHours(50), 'city_id' => $city->id, 'provider_type' => \App\Enums\Provider\ProviderType::Courier, 'assigned_admin_id' => $officer->id]);
        $this->makeProvider(ProviderStatus::UnderReview, ['submitted_at' => now()->subHours(1)]);
        $check = fn (array $filters, int $count) => $this->datatable(route('admin.verification.index'), $filters)->assertJsonPath('recordsFiltered', $count);

        $check([], 2);
        $check(['statuses' => ['under_review']], 1);
        $check(['statuses' => ['approved']], 2);
        $check(['provider_types' => ['courier']], 1);
        $check(['city_ids' => [$city->id]], 1);
        $check(['waiting_hours' => 24], 1);
        $check(['assigned_admin_ids' => [$officer->id]], 1);
        $check(['unassigned' => '1'], 1);
        $this->assertSame($match->id, $this->datatable(route('admin.verification.index'), ['waiting_hours' => 24])->json('data.0.id'));
    }

    public function test_claiming_assigns_the_officer_and_is_audited(): void
    {
        ['provider' => $provider] = $this->application();
        $officer = $this->actingAsAdmin(self::OFFICER);

        $this->postJson(route('admin.verification.claim', $provider))->assertOk();

        $this->assertSame($officer->id, $provider->refresh()->assigned_admin_id);
        $this->assertDatabaseHas('admin_audit_logs', ['auditable_type' => 'provider_profile', 'actor_id' => $officer->id, 'action' => AuditAction::Updated->value]);

        $this->actingAsAdmin([AdminPermission::DriversView]);
        $this->postJson(route('admin.verification.claim', $provider))->assertForbidden();
    }

    public function test_the_review_screen_lists_documents_checklist_and_decisions(): void
    {
        ['provider' => $provider, 'licence' => $licence] = $this->application();
        $this->actingAsAdmin(self::OFFICER);

        $this->get(route('admin.verification.review', $provider))
            ->assertOk()
            ->assertSee('data-review-screen', false)
            ->assertSee('data-document-viewer', false)
            ->assertSee('provider-documents', false)
            ->assertSee($licence->uuid, false)
            ->assertSee('data-review-approve', false)
            ->assertSee('data-review-more-info', false)
            ->assertSee('data-review-reject', false)
            ->assertSee('data-review-checklist', false)
            ->assertSee('data-review-vehicle', false);
    }

    public function test_a_read_only_role_sees_no_decision_buttons_and_gets_403_on_every_decision(): void
    {
        ['provider' => $provider, 'category' => $category, 'licence' => $licence, 'vehicle' => $vehicle] = $this->application();
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DocumentsView, AdminPermission::VehiclesView]);

        $this->get(route('admin.verification.review', $provider))
            ->assertOk()
            ->assertDontSee('data-review-approve', false)
            ->assertDontSee('data-review-more-info', false)
            ->assertDontSee('data-review-reject', false)
            ->assertSee(__('admin.verification.read_only'));
        $this->postJson(route('admin.verification.approve', $provider), ['approved_services' => [$category->id]])->assertForbidden();
        $this->postJson(route('admin.verification.request-information', $provider), ['reason' => 'Please upload again'])->assertForbidden();
        $this->postJson(route('admin.verification.reject', $provider), ['reason' => 'Not acceptable at all'])->assertForbidden();
        $this->postJson(route('admin.provider-documents.approve', $licence->uuid))->assertForbidden();
        $this->postJson(route('admin.provider-documents.reject', $licence->uuid), ['reason' => 'Not readable at all'])->assertForbidden();
        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertForbidden();
        $this->postJson(route('admin.vehicles.reject', $vehicle), ['reason' => 'Plate is not visible'])->assertForbidden();

        $this->assertSame(ProviderStatus::UnderReview, $provider->refresh()->status);
        $this->assertSame(DocumentStatus::Pending, $licence->refresh()->status);
        $this->assertSame(VehicleStatus::UnderReview, $vehicle->refresh()->status);
    }

    public function test_approval_needs_every_required_document_approved_and_at_least_one_service(): void
    {
        ['provider' => $provider, 'category' => $category, 'licence' => $licence] = $this->application();
        $this->actingAsAdmin(self::OFFICER);
        $approve = route('admin.verification.approve', $provider);

        $this->precognitive('POST', $approve, [])->assertStatus(422)->assertJsonValidationErrors(['approved_services']);
        $this->postJson($approve, ['approved_services' => [$category->id]])->assertStatus(422)->assertJsonPath('errors._code', 'provider_documents_incomplete');

        $this->postJson(route('admin.provider-documents.approve', $licence->uuid))->assertOk();
        $this->postJson($approve, ['approved_services' => [999999]])->assertStatus(422)->assertJsonPath('errors._code', 'provider_services_invalid');
        $this->assertSame(ProviderStatus::UnderReview, $provider->refresh()->status);
    }

    public function test_approving_records_services_status_history_audit_and_the_current_vehicle(): void
    {
        ['provider' => $provider, 'category' => $category, 'licence' => $licence, 'vehicle' => $vehicle] = $this->application();
        $extra = $this->bookableCategory();
        $this->requestService($provider, $extra);
        $officer = $this->actingAsAdmin(self::OFFICER);

        $this->postJson(route('admin.provider-documents.approve', $licence->uuid))->assertOk();
        $this->postJson(route('admin.vehicle-documents.approve', \App\Models\VehicleDocument::query()->firstOrFail()->uuid))->assertOk();
        $this->postJson(route('admin.vehicles.approve', $vehicle))->assertOk();
        $this->postJson(route('admin.verification.approve', $provider), ['approved_services' => [$category->id], 'note' => 'Documents are clear'])->assertOk();

        $provider->refresh();
        $this->assertSame(ProviderStatus::Approved, $provider->status);
        $this->assertNotNull($provider->approved_at);
        $this->assertSame($vehicle->id, $provider->current_vehicle_id, 'the first approved vehicle becomes the current one');
        $statuses = $provider->services()->pluck('status', 'service_category_id')->map(fn ($s) => $s->value)->all();
        $this->assertSame(['approved', 'rejected'], [$statuses[$category->id], $statuses[$extra->id]]);
        $this->assertDatabaseHas('provider_status_events', ['provider_profile_id' => $provider->id, 'to_status' => 'approved', 'actor_id' => $officer->id, 'reason' => 'Documents are clear']);
        $log = AdminAuditLog::query()->where('action', AuditAction::Approved->value)->where('auditable_type', 'provider_profile')->where('reason', 'Documents are clear')->sole();
        $this->assertSame(['under_review', 'approved', 'Documents are clear'], [$log->before['status'], $log->after['status'], $log->reason]);
        $this->assertSame([$category->id], $log->meta['approved_services']);

        $this->postJson(route('admin.verification.approve', $provider), ['approved_services' => [$category->id]])->assertForbidden();
        $this->assertTrue(app(\App\Services\Provider\ProviderEligibilityService::class)->check($provider, $category)->isEligible());
    }

    public function test_requesting_more_information_needs_a_reason_and_rejects_the_listed_documents_with_theirs(): void
    {
        ['provider' => $provider, 'licence' => $licence, 'registration' => $registration] = $this->application();
        $officer = $this->actingAsAdmin(self::OFFICER);
        $url = route('admin.verification.request-information', $provider);

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->precognitive('POST', $url, ['reason' => 'Please fix the documents', 'document_issues' => [$licence->uuid => 'x']])->assertStatus(422)->assertJsonValidationErrors(['document_issues.'.$licence->uuid]);
        $this->precognitive('POST', $url, ['reason' => 'Please fix the documents'])->assertNoContent();
        $this->assertSame(ProviderStatus::UnderReview, $provider->refresh()->status);

        $this->postJson($url, [
            'reason' => 'Please fix the documents',
            'document_issues' => [$licence->uuid => 'The licence photo is blurry', $registration->uuid => ''],
        ])->assertOk();

        $provider->refresh();
        $this->assertSame(ProviderStatus::MoreInformationRequired, $provider->status);
        $this->assertSame('Please fix the documents', $provider->status_reason);
        $licence->refresh();
        $this->assertSame([DocumentStatus::Rejected, 'The licence photo is blurry', $officer->id], [$licence->status, $licence->rejection_reason, $licence->reviewed_by_admin_id]);
        $this->assertSame(DocumentStatus::Pending, $registration->refresh()->status, 'documents without text are left alone');
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::MoreInfoRequested->value, 'reason' => 'Please fix the documents']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Rejected->value, 'reason' => 'The licence photo is blurry']);
    }

    public function test_the_provider_sees_the_reasons_and_can_resubmit(): void
    {
        ['provider' => $provider, 'licence' => $licence] = $this->application();
        $this->actingAsAdmin(self::OFFICER);
        $this->postJson(route('admin.verification.request-information', $provider), ['reason' => 'The licence is not readable', 'document_issues' => [$licence->uuid => 'Blurry photo of the licence']])->assertOk();

        $this->actingAsProvider($provider);
        $response = $this->getJson('/api/v1/provider/application')->assertOk()
            ->assertJsonPath('data.status', 'more_information_required')
            ->assertJsonPath('data.status_reason', 'The licence is not readable');
        $document = collect($response->json('data.required_documents'))->firstWhere('document_type.code', 'licence');
        $this->assertSame(['rejected', 'Blurry photo of the licence'], [$document['state'], $document['document']['rejection_reason']]);
        $this->assertContains('replace_rejected_document', array_column($response->json('data.tasks'), 'task'));
    }

    public function test_rejecting_needs_a_reason_and_leaves_the_application_resubmittable(): void
    {
        ['provider' => $provider] = $this->application();
        $this->actingAsAdmin(self::OFFICER);
        $url = route('admin.verification.reject', $provider);

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->assertSame(ProviderStatus::UnderReview, $provider->refresh()->status);

        $this->postJson($url, ['reason' => 'The documents do not match the applicant'])->assertOk();
        $this->assertSame([ProviderStatus::Rejected, 'The documents do not match the applicant'], [$provider->refresh()->status, $provider->status_reason]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Rejected->value, 'auditable_type' => 'provider_profile', 'reason' => 'The documents do not match the applicant']);

        $this->postJson($url, ['reason' => 'Trying a second rejection'])->assertForbidden();
    }

    public function test_reject_needs_its_own_permission_apart_from_verify(): void
    {
        ['provider' => $provider] = $this->application();
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversVerify]);

        $this->postJson(route('admin.verification.reject', $provider), ['reason' => 'No reject permission'])->assertForbidden();
        $this->get(route('admin.verification.review', $provider))->assertOk()->assertDontSee('data-review-reject', false)->assertSee('data-review-approve', false);
    }

    public function test_documents_are_decided_one_by_one_with_audit_and_reasons(): void
    {
        ['licence' => $licence, 'registration' => $registration, 'provider' => $provider] = $this->application();
        $officer = $this->actingAsAdmin(self::OFFICER);

        $this->postJson(route('admin.provider-documents.reject', $licence->uuid), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.provider-documents.reject', $licence->uuid), ['reason' => 'Expired according to the photo'])->assertOk();
        $this->assertSame([DocumentStatus::Rejected, 'Expired according to the photo'], [$licence->refresh()->status, $licence->rejection_reason]);

        $this->postJson(route('admin.provider-documents.approve', $licence->uuid))->assertForbidden();
        $this->postJson(route('admin.vehicle-documents.approve', $registration->uuid))->assertOk();
        $this->assertSame([DocumentStatus::Approved, $officer->id], [$registration->refresh()->status, $registration->reviewed_by_admin_id]);

        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Rejected->value, 'auditable_type' => 'provider_profile', 'auditable_id' => (string) $provider->id, 'reason' => 'Expired according to the photo']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Approved->value, 'auditable_type' => 'vehicle']);
    }

    public function test_an_expired_document_cannot_be_approved(): void
    {
        ['licence' => $licence] = $this->application();
        $licence->forceFill(['expires_at' => now()->subDay()->toDateString()])->save();
        $this->actingAsAdmin(self::OFFICER);

        $this->postJson(route('admin.provider-documents.approve', $licence->uuid))->assertStatus(422)->assertJsonPath('errors._code', 'document_already_expired');
    }

    public function test_the_service_decision_is_only_for_applications_that_exist(): void
    {
        $provider = $this->makeProvider(ProviderStatus::DocumentsPending);
        $this->actingAsAdmin(self::OFFICER);

        $this->postJson(route('admin.verification.approve', $provider), ['approved_services' => [1]])->assertForbidden();
        $this->postJson(route('admin.verification.claim', $provider))->assertForbidden();
        $this->assertSame(ServiceApprovalStatus::Requested->value, 'requested');
    }
}
