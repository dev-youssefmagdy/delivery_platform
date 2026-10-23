<?php

namespace Tests\Feature\Admin\Provider;

use App\DataTables\Admin\Provider\ProvidersDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Provider\ProviderType;
use App\Enums\Provider\ServiceApprovalStatus;
use App\Models\AdminAuditLog;
use App\Models\City;
use App\Services\Provider\ProviderAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsProviders;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for the Providers listing and show page.
 */
class ProviderAdminTest extends TestCase
{
    use BuildsProviders;
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_blocked_and_unrelated_permissions_are_not_enough(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.providers.index')));

        $provider = $this->makeProvider();
        $this->actingAsAdmin([AdminPermission::CustomersView, AdminPermission::VehiclesView, AdminPermission::DocumentsView]);

        $this->get(route('admin.providers.index'))->assertForbidden();
        $this->datatable(route('admin.providers.index'))->assertForbidden();
        $this->getJson(route('admin.providers.show', $provider))->assertForbidden();
        $this->getJson(route('admin.providers.tabs.documents', $provider))->assertForbidden();
        $this->postJson(route('admin.providers.suspend', $provider), ['reason' => 'Some good reason'])->assertForbidden();
    }

    public function test_index_has_a_collapsed_filters_card_and_every_appendix_c_filter_works(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView]);
        $category = $this->bookableCategory();
        $cairo = City::factory()->create();
        $match = $this->makeProvider(ProviderStatus::Approved, ['city_id' => $cairo->id, 'provider_type' => ProviderType::Courier, 'is_online' => true], ['name' => 'Karim Fouad', 'phone_e164' => '+201112223344']);
        $this->requestService($match, $category, ServiceApprovalStatus::Approved);
        $other = $this->makeProvider(ProviderStatus::UnderReview, [], ['name' => 'Someone Else']);
        $expiry = $this->documentType(['code' => 'licence', 'requires_expiry' => true]);
        $this->providerDocument($match, $expiry, DocumentStatus::Approved, now()->addDays(10)->toDateString());
        $this->providerDocument($other, $expiry, DocumentStatus::Approved, now()->addDays(200)->toDateString());

        $this->get(route('admin.providers.index'))->assertOk()->assertSee('data-filters-for="'.ProvidersDataTable::TABLE_ID.'"', false);
        $check = fn (array $filters, int $count) => $this->datatable(route('admin.providers.index'), $filters)->assertJsonPath('recordsFiltered', $count);

        $check([], 2);
        $check(['search' => 'Karim'], 1);
        $check(['search' => $match->uuid], 1);
        $check(['search' => '+201112223344'], 1);
        $check(['search' => '3344'], 1);
        $check(['statuses' => ['approved']], 1);
        $check(['provider_types' => ['courier']], 1);
        $check(['service_category_ids' => [$category->id]], 1);
        $check(['city_ids' => [$cairo->id]], 1);
        $check(['online' => '1'], 1);
        $check(['online' => '0'], 1);
        $check(['expiring_within_days' => 30], 1);
        $check(['expiring_within_days' => 365], 2);
        $check(['statuses' => ['approved'], 'online' => '0'], 0);
    }

    public function test_the_listing_never_exposes_phone_numbers_or_birth_dates(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversViewPii]);
        $this->makeProvider(ProviderStatus::Approved, [], ['phone_e164' => '+201112223344', 'email' => 'secret@example.test']);

        $json = $this->datatable(route('admin.providers.index'))->assertOk()->assertJsonPath('recordsTotal', 1)->getContent();

        $this->assertStringNotContainsString('+201112223344', (string) $json);
        $this->assertStringNotContainsString('secret@example.test', (string) $json);
        $this->assertStringNotContainsString('date_of_birth', (string) $json);
        $this->assertStringContainsString('3344', (string) $json, 'the masked phone shows the last digits');
    }

    public function test_show_page_renders_masked_contact_details_and_eligibility_reasons(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView]);
        $provider = $this->makeProvider(ProviderStatus::Approved, [], ['phone_e164' => '+201112223344']);

        $this->get(route('admin.providers.show', $provider))
            ->assertOk()
            ->assertDontSee('+201112223344')
            ->assertSee('data-reveal-field="phone"', false)
            ->assertDontSee('data-reveal-field="date_of_birth"', false)
            ->assertSee($provider->uuid);
        $this->get(route('admin.providers.show', $provider))->assertDontSee(__('admin.providers.reveal.button'));
    }

    public function test_reveal_needs_view_pii_and_is_audited_without_values(): void
    {
        $provider = $this->makeProvider(ProviderStatus::Approved, ['date_of_birth' => '1990-05-06'], ['phone_e164' => '+201112223344', 'email' => 'omar@example.test']);

        $this->actingAsAdmin([AdminPermission::DriversView]);
        $this->postJson(route('admin.providers.reveal', $provider))->assertForbidden();

        $admin = $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversViewPii]);
        $this->postJson(route('admin.providers.reveal', $provider))
            ->assertOk()->assertJsonPath('data.phone', '+201112223344')->assertJsonPath('data.email', 'omar@example.test')->assertJsonPath('data.date_of_birth', '1990-05-06');

        $log = AdminAuditLog::query()->where('action', AuditAction::PhoneRevealed->value)->sole();
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('provider_profile', $log->auditable_type);
        $this->assertSame(['phone', 'email', 'date_of_birth'], $log->meta['fields']);
        $this->assertStringNotContainsString('+201112223344', (string) json_encode($log->toArray()));
    }

    public function test_suspend_validates_requires_a_reason_and_records_audit_and_history(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversSuspend]);
        $provider = $this->makeProvider(ProviderStatus::Approved, ['is_online' => true]);
        $url = route('admin.providers.suspend', $provider);
        $end = now()->addDays(2)->setTimezone((string) config('admin.display_timezone'))->format('Y-m-d H:i');

        $this->precognitive('POST', $url, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->precognitive('POST', $url, ['reason' => 'Repeated complaints', 'expires_at' => '2020-01-01 10:00'])->assertStatus(422)->assertJsonValidationErrors(['expires_at']);
        $this->precognitive('POST', $url, ['reason' => 'Repeated complaints', 'permanent' => '1', 'expires_at' => $end])->assertStatus(422)->assertJsonValidationErrors(['expires_at']);
        $this->precognitive('POST', $url, ['reason' => 'Repeated complaints', 'expires_at' => $end])->assertNoContent();
        $this->assertSame(ProviderStatus::Approved, $provider->refresh()->status, 'precognition never persists');

        $this->postJson($url, ['reason' => 'Repeated complaints', 'expires_at' => $end])->assertOk();

        $provider->refresh();
        $this->assertSame(ProviderStatus::TemporarilySuspended, $provider->status);
        $this->assertSame('Repeated complaints', $provider->status_reason);
        $this->assertFalse($provider->is_online);
        $this->assertTrue($provider->status_expires_at->between(now()->addDays(2)->subMinutes(2), now()->addDays(2)->addMinutes(2)));
        $log = AdminAuditLog::query()->where('action', AuditAction::Suspended->value)->sole();
        $this->assertSame([$admin->id, 'Repeated complaints', 'approved', 'temporarily_suspended'], [$log->actor_id, $log->reason, $log->before['status'], $log->after['status']]);
        $this->assertDatabaseHas('provider_status_events', ['provider_profile_id' => $provider->id, 'to_status' => 'temporarily_suspended', 'reason' => 'Repeated complaints', 'actor_id' => $admin->id]);

        // Already suspended temporarily: another temporary suspension is refused, escalating to permanent is allowed.
        $this->postJson($url, ['reason' => 'Again and again'])->assertStatus(422)->assertJsonPath('errors._code', 'provider_status_transition_invalid');
        $this->postJson($url, ['reason' => 'Fraud confirmed later', 'permanent' => '1'])->assertOk();
        $this->assertSame(ProviderStatus::PermanentlySuspended, $provider->refresh()->status);
        $this->postJson($url, ['reason' => 'Nothing left to do'])->assertForbidden();
    }

    public function test_reactivate_needs_a_reason_and_a_permanent_suspension_cannot_be_lifted(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversSuspend]);
        $temporary = $this->makeProvider(ProviderStatus::TemporarilySuspended);
        $permanent = $this->makeProvider(ProviderStatus::PermanentlySuspended);

        $this->postJson(route('admin.providers.reactivate', $temporary), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.providers.reactivate', $temporary), ['reason' => 'Complaint was unfounded'])->assertOk();
        $this->assertSame(ProviderStatus::Approved, $temporary->refresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Reactivated->value, 'reason' => 'Complaint was unfounded']);

        $this->postJson(route('admin.providers.reactivate', $permanent), ['reason' => 'Trying to lift it'])->assertForbidden();
        $this->assertSame(ProviderStatus::PermanentlySuspended, $permanent->refresh()->status);

        $this->postJson(route('admin.providers.suspend', $this->makeProvider(ProviderStatus::Approved)), ['reason' => 'Fraud confirmed', 'permanent' => '1'])->assertOk();
        $this->assertSame(1, \App\Models\ProviderProfile::query()->where('status', ProviderStatus::PermanentlySuspended)->where('status_reason', 'Fraud confirmed')->count());
    }

    public function test_suspend_is_only_for_approved_providers_and_needs_its_permission(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversVerify]);
        $approved = $this->makeProvider(ProviderStatus::Approved);

        $this->postJson(route('admin.providers.suspend', $approved), ['reason' => 'No permission here'])->assertForbidden();

        $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversSuspend]);
        $this->postJson(route('admin.providers.suspend', $this->makeProvider(ProviderStatus::UnderReview)), ['reason' => 'Not approved yet'])->assertForbidden();
    }

    public function test_temporary_suspensions_end_by_themselves_through_the_scheduled_job(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::DriversSuspend]);
        $expired = $this->makeProvider(ProviderStatus::Approved);
        $running = $this->makeProvider(ProviderStatus::Approved);
        $service = app(ProviderAccountService::class);
        $service->suspend($expired, new \App\DTOs\Provider\SuspendProviderData('Cooling off period', false, now()->addMinutes(10)->toImmutable()), $admin);
        $service->suspend($running, new \App\DTOs\Provider\SuspendProviderData('Long investigation', false, now()->addDays(2)->toImmutable()), $admin);

        $this->travel(11)->minutes();
        (new \App\Jobs\Provider\ReactivateExpiredProviderSuspensions)->handle($service);

        $this->assertSame(ProviderStatus::Approved, $expired->refresh()->status);
        $this->assertSame(ProviderStatus::TemporarilySuspended, $running->refresh()->status);
        $log = AdminAuditLog::query()->where('action', AuditAction::Reactivated->value)->sole();
        $this->assertSame('system', $log->actor_type);
        $this->assertSame(0, $service->reactivateExpired(), 'idempotent');

        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->first(static fn ($e): bool => $e->description === 'providers:reactivate-expired');
        $this->assertSame('* * * * *', $event?->expression);
    }

    public function test_service_decisions_after_approval_need_a_reason_and_are_audited(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::DriversView, AdminPermission::DriversVerify]);
        $provider = $this->makeProvider(ProviderStatus::Approved);
        $category = $this->bookableCategory();
        $this->requestService($provider, $category, ServiceApprovalStatus::Requested);
        $approve = route('admin.providers.services.approve', [$provider, $category->id]);

        $this->postJson($approve, [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson($approve, ['reason' => 'Vehicle suits this service'])->assertOk();
        $this->assertSame(ServiceApprovalStatus::Approved, $provider->services()->firstOrFail()->status);
        $this->postJson(route('admin.providers.services.reject', [$provider, $category->id]), ['reason' => 'Complaints on this service'])->assertOk();
        $this->assertSame(ServiceApprovalStatus::Rejected, $provider->services()->firstOrFail()->status);
        $this->assertSame(2, AdminAuditLog::query()->where('actor_id', $admin->id)->where('auditable_type', 'provider_profile')->count());

        $this->postJson(route('admin.providers.services.approve', [$provider, 999999]), ['reason' => 'Not requested by them'])->assertStatus(422)->assertJsonPath('errors._code', 'provider_service_not_found');
        $this->datatable(route('admin.providers.tabs.services', $provider))->assertOk()->assertJsonPath('recordsTotal', 1);
    }

    public function test_every_tab_is_served_as_its_own_datatable_and_the_page_lists_the_tabs(): void
    {
        $this->actingAsAdmin([AdminPermission::DriversView]);
        $provider = $this->makeProvider(ProviderStatus::Approved);
        $this->vehicleFor($provider);
        $this->providerDocument($provider, $this->documentType(['code' => 'licence']));

        foreach (['services', 'vehicles', 'documents', 'history', 'deletion-requests', 'audit'] as $tab) {
            $this->get(route('admin.providers.tabs.'.$tab, $provider))->assertOk()->assertSee('data-table', false);
        }

        $this->datatable(route('admin.providers.tabs.vehicles', $provider))->assertJsonPath('recordsTotal', 1);
        $this->datatable(route('admin.providers.tabs.documents', $provider))->assertJsonPath('recordsTotal', 1);
        $page = $this->get(route('admin.providers.show', $provider))->assertOk();

        foreach (['services', 'vehicles', 'documents', 'history', 'jobs', 'earnings', 'ratings', 'deletion-requests', 'audit'] as $tab) {
            $page->assertSee(route('admin.providers.tabs.'.$tab, $provider), false);
        }
    }
}
