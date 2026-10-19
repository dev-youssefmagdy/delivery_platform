<?php

namespace Tests\Feature\Admin\Admins;

use App\DataTables\Admin\Admins\AdminsDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Admin\AdminRole;
use App\Enums\Admin\AdminStatus;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\AdminSession;
use App\Notifications\Admin\AdminPasswordNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithAdmin;
use Tests\Support\AdminTotp;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Admin users, plus the access-safety rules.
 */
class AdminUsersAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.admins.index')));
        $this->assertGuestBlocked($this->get(route('admin.admins.create')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $target = Admin::factory()->create();

        $this->get(route('admin.admins.index'))->assertForbidden();
        $this->getJson(route('admin.admins.show', $target))->assertForbidden()->assertJsonPath('errors._code', 'forbidden');
        $this->datatable(route('admin.admins.index'))->assertForbidden();
        $this->postJson(route('admin.admins.store'), $this->validPayload())->assertForbidden();
        $this->postJson(route('admin.admins.suspend', $target), ['reason' => 'Because of reasons'])->assertForbidden();

        // View permission alone does not allow writes.
        $this->actingAsAdmin([AdminPermission::AdminsView]);
        $this->get(route('admin.admins.index'))->assertOk();
        $this->postJson(route('admin.admins.store'), $this->validPayload())->assertForbidden();
        $this->putJson(route('admin.admins.update', $target), $this->validPayload() + ['lock_version' => 0])->assertForbidden();
    }

    public function test_index_renders_with_a_collapsed_filters_card_and_permission_aware_buttons(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsView]);

        $this->get(route('admin.admins.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.AdminsDataTable::TABLE_ID.'"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('id="'.AdminsDataTable::TABLE_ID.'"', false)
            ->assertDontSee(route('admin.admins.create'));

        $this->actingAsAdmin([AdminPermission::AdminsView, AdminPermission::AdminsManage]);
        $this->get(route('admin.admins.index'))->assertSee(route('admin.admins.create'));
    }

    public function test_datatable_respects_every_filter(): void
    {
        $me = $this->actingAsAdmin([AdminPermission::AdminsView]);
        $match = Admin::factory()->create(['name' => 'Needle Person', 'email' => 'needle@example.test']);
        $match->assignRole(AdminRole::FinanceManager->value);
        AdminTotp::enable($match);
        Admin::factory()->suspended()->create(['name' => 'Someone Else']);

        $url = route('admin.admins.index');

        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', 3);
        $this->datatable($url, ['search' => 'needle@'])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.id', $match->id);
        $this->datatable($url, ['search' => 'Needle'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['role' => AdminRole::FinanceManager->value])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['role' => AdminRole::SuperAdmin->value])->assertJsonPath('recordsFiltered', 0);
        $this->datatable($url, ['statuses' => [AdminStatus::Suspended->value]])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['statuses' => [AdminStatus::Active->value]])->assertJsonPath('recordsFiltered', 2);
        $this->datatable($url, ['mfa_enabled' => '1'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['mfa_enabled' => '0'])->assertJsonPath('recordsFiltered', 2);
        $this->datatable($url, ['created_at' => ['from' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 0);
        $this->datatable($url, ['created_at' => ['from' => now()->subDay()->toDateString(), 'to' => now()->addDay()->toDateString()]])->assertJsonPath('recordsFiltered', 3);

        $this->assertNotNull($me);
        // Secrets never reach the listing payload.
        $this->assertStringNotContainsString('mfa_secret', (string) $this->datatable($url)->getContent());
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsManage]);

        $this->precognitive('POST', route('admin.admins.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);

        $this->precognitive('POST', route('admin.admins.store'), ['name' => 'X', 'email' => 'not-an-email', 'roles' => ['ghost_role']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'roles.0']);

        $this->precognitive('POST', route('admin.admins.store'), $this->validPayload())->assertNoContent();

        $this->assertDatabaseMissing('admins', ['email' => 'new.admin@example.test']);
    }

    public function test_store_creates_the_admin_audits_and_emails_an_invitation_without_a_password(): void
    {
        Notification::fake();
        $actor = $this->actingAsAdmin([AdminPermission::AdminsManage], ['locale' => null]);
        $actor->assignRole(AdminRole::SuperAdmin->value);

        $response = $this->postJson(route('admin.admins.store'), $this->validPayload(['roles' => [AdminRole::DispatchAgent->value]]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $admin = Admin::query()->where('email', 'new.admin@example.test')->sole();
        $response->assertJsonPath('data.redirect', route('admin.admins.show', $admin));
        $this->assertTrue($admin->hasRole(AdminRole::DispatchAgent->value));
        $this->assertSame(AdminStatus::Active, $admin->status);
        Notification::assertSentTo($admin, AdminPasswordNotification::class, fn (AdminPasswordNotification $n) => $n->invited);

        $log = AdminAuditLog::query()->where('action', AuditAction::Created->value)->where('auditable_id', (string) $admin->id)->sole();
        $this->assertSame('admin', $log->actor_type);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame([AdminRole::DispatchAgent->value], $log->after['roles']);
        $this->assertArrayNotHasKey('password', $log->after);
        $this->assertArrayNotHasKey('mfa_secret', $log->after);

        // A duplicate email (even with different case) is a validation error, not a crash.
        $this->postJson(route('admin.admins.store'), $this->validPayload(['email' => 'NEW.admin@example.test']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_writes_before_after_with_roles_and_rejects_a_stale_lock_version(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsManage, AdminPermission::AuditLogsView]);
        $target = Admin::factory()->create(['name' => 'Before Name']);
        $target->assignRole(AdminRole::DispatchAgent->value);

        $payload = $this->validPayload(['name' => 'After Name', 'email' => $target->email, 'roles' => []]) + ['lock_version' => 0];

        $this->putJson(route('admin.admins.update', $target), $payload)->assertOk();

        $target->refresh();
        $this->assertSame('After Name', $target->name);
        $this->assertSame(1, $target->lock_version);
        $this->assertFalse($target->hasRole(AdminRole::DispatchAgent->value));

        $log = AdminAuditLog::query()->where('action', AuditAction::Updated->value)->where('auditable_id', (string) $target->id)->sole();
        $this->assertSame('Before Name', $log->before['name']);
        $this->assertSame('After Name', $log->after['name']);
        $this->assertSame([AdminRole::DispatchAgent->value], $log->before['roles']);
        $this->assertSame([], $log->after['roles']);

        $this->putJson(route('admin.admins.update', $target), $payload)
            ->assertStatus(409)
            ->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_suspend_requires_a_reason_signs_the_admin_out_and_can_be_reversed(): void
    {
        $actor = $this->actingAsAdmin([AdminPermission::AdminsManage]);
        $target = Admin::factory()->create();
        AdminSession::query()->create(['admin_id' => $target->id, 'session_id' => 'target-session', 'last_active_at' => now()]);

        $this->postJson(route('admin.admins.suspend', $target), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->postJson(route('admin.admins.suspend', $target), ['reason' => 'abc'])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->assertSame(AdminStatus::Active, $target->refresh()->status);

        $this->postJson(route('admin.admins.suspend', $target), ['reason' => 'Left the company'])->assertOk();

        $this->assertSame(AdminStatus::Suspended, $target->refresh()->status);
        $this->assertSame(0, AdminSession::query()->where('admin_id', $target->id)->count());
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::Suspended->value, 'auditable_id' => (string) $target->id, 'reason' => 'Left the company', 'actor_id' => $actor->id,
        ]);

        $this->postJson(route('admin.admins.reactivate', $target), [])->assertOk();
        $this->assertSame(AdminStatus::Active, $target->refresh()->status);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Reactivated->value, 'auditable_id' => (string) $target->id]);
    }

    public function test_nobody_can_suspend_or_delete_themselves(): void
    {
        $me = $this->actingAsAdmin([AdminPermission::AdminsManage]);

        $this->postJson(route('admin.admins.suspend', $me), ['reason' => 'Trying it out'])->assertForbidden();
        $this->deleteJson(route('admin.admins.destroy', $me))->assertForbidden();
        $this->assertSame(AdminStatus::Active, $me->refresh()->status);
    }

    public function test_delete_is_soft_audited_and_ends_sessions(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsManage]);
        $target = Admin::factory()->create();
        AdminSession::query()->create(['admin_id' => $target->id, 'session_id' => 's1', 'last_active_at' => now()]);

        $this->deleteJson(route('admin.admins.destroy', $target), ['reason' => 'Account no longer needed'])
            ->assertOk()
            ->assertJsonPath('data.reload_table', [AdminsDataTable::TABLE_ID]);

        $this->assertSoftDeleted($target);
        $this->assertSame(0, AdminSession::query()->where('admin_id', $target->id)->count());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_id' => (string) $target->id, 'reason' => 'Account no longer needed']);
        // The email stays reserved so an old account can never be silently re-created.
        $this->postJson(route('admin.admins.store'), $this->validPayload(['email' => $target->email]))->assertStatus(422);
    }

    public function test_the_last_active_super_admin_cannot_be_suspended_deleted_or_demoted(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsManage]);
        $only = Admin::factory()->create();
        $only->assignRole(AdminRole::SuperAdmin->value);

        $this->deleteJson(route('admin.admins.destroy', $only))->assertStatus(422)->assertJsonPath('errors._code', 'last_super_admin');
        $this->postJson(route('admin.admins.suspend', $only), ['reason' => 'Rotating duties'])->assertStatus(422)->assertJsonPath('errors._code', 'last_super_admin');
        $this->putJson(route('admin.admins.update', $only), $this->validPayload(['email' => $only->email, 'roles' => []]) + ['lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'last_super_admin');

        $this->assertTrue($only->refresh()->isSuperAdmin());
        $this->assertSame(AdminStatus::Active, $only->status);
        $this->assertNull($only->deleted_at);

        // With a second active Super Admin the first can be suspended.
        $second = Admin::factory()->create();
        $second->assignRole(AdminRole::SuperAdmin->value);
        $this->postJson(route('admin.admins.suspend', $only), ['reason' => 'Rotating duties'])->assertOk();
        // ...which makes the second one the last again.
        $this->postJson(route('admin.admins.suspend', $second), ['reason' => 'Rotating duties'])->assertStatus(422)->assertJsonPath('errors._code', 'last_super_admin');
    }

    public function test_a_super_admin_still_cannot_suspend_or_delete_themselves(): void
    {
        $me = $this->actingAsAdmin();
        $me->assignRole(AdminRole::SuperAdmin->value);
        $other = Admin::factory()->create();
        $other->assignRole(AdminRole::SuperAdmin->value);

        // Super Admin passes policies through Gate::before, so the service enforces the rule.
        $this->postJson(route('admin.admins.suspend', $me), ['reason' => 'Trying it out'])->assertStatus(422)->assertJsonPath('errors._code', 'cannot_modify_self');
        $this->deleteJson(route('admin.admins.destroy', $me))->assertStatus(422)->assertJsonPath('errors._code', 'cannot_modify_self');
    }

    public function test_a_super_admin_cannot_remove_their_own_super_admin_role(): void
    {
        $me = $this->actingAsAdmin([AdminPermission::AdminsManage]);
        $me->assignRole(AdminRole::SuperAdmin->value);
        $other = Admin::factory()->create();
        $other->assignRole(AdminRole::SuperAdmin->value);

        $this->putJson(route('admin.admins.update', $me), $this->validPayload(['email' => $me->email, 'roles' => []]) + ['lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'cannot_remove_own_super_admin');
        $this->assertTrue($me->refresh()->isSuperAdmin());
    }

    public function test_only_a_super_admin_can_grant_the_super_admin_role(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsManage]);
        $target = Admin::factory()->create();

        $this->postJson(route('admin.admins.store'), $this->validPayload(['roles' => [AdminRole::SuperAdmin->value]]))
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'super_admin_only');
        $this->putJson(route('admin.admins.update', $target), $this->validPayload(['email' => $target->email, 'roles' => [AdminRole::SuperAdmin->value]]) + ['lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'super_admin_only');
        $this->assertFalse($target->refresh()->isSuperAdmin());
    }

    public function test_an_admin_cannot_grant_roles_with_permissions_they_do_not_hold(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsManage, AdminPermission::BookingsView]);
        $target = Admin::factory()->create();

        // Dispatch agent includes bookings.manage etc., which this admin lacks.
        $this->putJson(route('admin.admins.update', $target), $this->validPayload(['email' => $target->email, 'roles' => [AdminRole::FinanceManager->value]]) + ['lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'role_exceeds_own_permissions');
        $this->assertFalse($target->refresh()->hasRole(AdminRole::FinanceManager->value));
    }

    public function test_show_and_activity_pages_render_with_effective_permissions(): void
    {
        $this->actingAsAdmin([AdminPermission::AdminsView]);
        $target = Admin::factory()->create();
        $target->assignRole(AdminRole::DispatchAgent->value);

        $this->get(route('admin.admins.show', $target), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee($target->email)
            ->assertSee('Dispatch / Live Operations Agent')
            ->assertSee(route('admin.admins.activity', $target))
            ->assertDontSee(route('admin.admins.edit', $target));

        $this->get(route('admin.admins.activity', $target))->assertOk();

        $this->actingAsAdmin([AdminPermission::AdminsView, AdminPermission::AdminsManage]);
        $this->get(route('admin.admins.edit', $target), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee('name="lock_version"', false)
            ->assertSee('name="roles[]"', false);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Admin',
            'email' => 'new.admin@example.test',
            'phone' => '+201000000001',
            'locale' => 'en',
            'roles' => [],
        ], $overrides);
    }
}
