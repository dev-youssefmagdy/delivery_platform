<?php

namespace Tests\Feature\Admin\Audit;

use App\DataTables\Admin\Audit\AuditLogsDataTable;
use App\DataTables\Admin\Audit\LoginActivityDataTable;
use App\Enums\Admin\AdminLoginOutcome;
use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\AdminLoginEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for the read-only Audit logs and Login activity screens.
 */
class AuditLogsAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.audit-logs.index')));
        $this->assertGuestBlocked($this->get(route('admin.login-activity.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin([AdminPermission::LoginActivityView]);
        $log = $this->log();

        $this->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->getJson(route('admin.audit-logs.show', $log))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.audit-logs.index'))->assertForbidden();

        $this->actingAsAdmin([AdminPermission::AuditLogsView]);
        $this->get(route('admin.login-activity.index'))->assertForbidden();
        $this->datatable(route('admin.login-activity.index'))->assertForbidden();
    }

    public function test_the_audit_trail_is_read_only(): void
    {
        $this->actingAsAdmin([AdminPermission::AuditLogsView, AdminPermission::AdminsManage]);
        $log = $this->log();

        $this->postJson(route('admin.audit-logs.index'))->assertStatus(405);
        $this->deleteJson(route('admin.audit-logs.show', $log))->assertStatus(405);
        $this->putJson(route('admin.audit-logs.show', $log))->assertStatus(405);
        $this->assertModelExists($log);
    }

    public function test_index_has_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::AuditLogsView]);

        $this->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.AuditLogsDataTable::TABLE_ID.'"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('id="'.AuditLogsDataTable::TABLE_ID.'"', false);

        $this->actingAsAdmin([AdminPermission::LoginActivityView]);
        $this->get(route('admin.login-activity.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.LoginActivityDataTable::TABLE_ID.'"', false);
    }

    public function test_audit_datatable_respects_every_filter(): void
    {
        $actor = $this->actingAsAdmin([AdminPermission::AuditLogsView]);
        $other = Admin::factory()->create(['name' => 'Other Actor']);
        $match = $this->log(['actor_id' => $other->id, 'action' => AuditAction::Suspended, 'auditable_type' => 'admin', 'auditable_id' => '77', 'reason' => 'Because']);
        $this->log(['actor_id' => $actor->id, 'action' => AuditAction::Created, 'auditable_type' => 'role', 'auditable_id' => '5']);
        $url = route('admin.audit-logs.index');

        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable($url, ['actor_id' => (string) $other->id])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['actions' => [AuditAction::Suspended->value]])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['actions' => [AuditAction::Suspended->value, AuditAction::Created->value]])->assertJsonPath('recordsFiltered', 2);
        $this->datatable($url, ['target_type' => 'role'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['target_id' => '77'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['has_reason' => '1'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['has_reason' => '0'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['created_at' => ['from' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);
        $this->datatable($url, ['created_at' => ['to' => now()->subDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);

        $row = $this->datatable($url, ['actor_id' => (string) $other->id])->json('data.0');
        $this->assertStringContainsString('Other Actor', $row['actor']);
        // Payloads are only on the show page.
        $this->assertArrayNotHasKey('before', $row);
        $this->assertArrayNotHasKey('after', $row);
    }

    public function test_show_page_renders_a_before_after_diff_with_secrets_masked(): void
    {
        $this->actingAsAdmin([AdminPermission::AuditLogsView]);
        $log = $this->log([
            'before' => ['name' => 'Old', 'status' => 'active', 'api_token' => 'super-secret-1', 'roles' => ['a']],
            'after' => ['name' => 'New', 'status' => 'active', 'api_token' => 'super-secret-2', 'roles' => ['a', 'b']],
            'reason' => 'Reviewed by compliance',
        ]);

        $page = $this->get(route('admin.audit-logs.show', $log), ['Accept-Language' => 'en'])->assertOk();

        $page->assertSee('Old')->assertSee('New')->assertSee('Reviewed by compliance')->assertSee('••••••');
        $page->assertDontSee('super-secret-1')->assertDontSee('super-secret-2');
        // Only the changed rows are highlighted.
        $this->assertSame(3, substr_count((string) $page->getContent(), 'bg-warning-50/50'));
    }

    public function test_login_activity_datatable_respects_every_filter(): void
    {
        $this->actingAsAdmin([AdminPermission::LoginActivityView]);
        $admin = Admin::factory()->create();
        $match = AdminLoginEvent::query()->create(['admin_id' => $admin->id, 'email' => 'needle@example.test', 'outcome' => AdminLoginOutcome::InvalidCredentials, 'ip' => '10.0.0.7', 'user_agent' => 'UA']);
        AdminLoginEvent::query()->create(['admin_id' => $admin->id, 'email' => 'other@example.test', 'outcome' => AdminLoginOutcome::Success, 'ip' => '10.0.0.8', 'user_agent' => 'UA']);
        $url = route('admin.login-activity.index');

        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 2);
        $this->datatable($url, ['email' => 'needle'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['outcomes' => [AdminLoginOutcome::Success->value]])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['ip' => '10.0.0.7'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['created_at' => ['from' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);
    }

    public function test_the_admin_lookup_returns_names_only(): void
    {
        $this->actingAsAdmin();
        Admin::factory()->create(['name' => 'Findable Person', 'email' => 'secret.mail@example.test']);

        $response = $this->getJson(route('admin.lookups', ['lookup' => 'admins', 'q' => 'Findable']))->assertOk();

        $this->assertSame('Findable Person', $response->json('data.0.text'));
        $this->assertStringNotContainsString('secret.mail', (string) $response->getContent());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function log(array $attributes = []): AdminAuditLog
    {
        return AdminAuditLog::query()->create($attributes + [
            'actor_type' => 'admin',
            'actor_id' => null,
            'action' => AuditAction::Updated,
            'auditable_type' => 'admin',
            'auditable_id' => '1',
        ]);
    }
}
