<?php

namespace Tests\Feature\Admin\Roles;

use App\DataTables\Admin\Roles\RolesDataTable;
use App\Enums\Admin\AdminPermission;
use App\Enums\Admin\AdminRole;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for Roles and the permission matrix.
 */
class RolesAdminTest extends TestCase
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
        $this->assertGuestBlocked($this->get(route('admin.roles.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_json_and_datatable(): void
    {
        $this->actingAsAdmin();
        $role = $this->customRole();

        $this->get(route('admin.roles.index'))->assertForbidden();
        $this->getJson(route('admin.roles.show', $role))->assertForbidden();
        $this->datatable(route('admin.roles.index'))->assertForbidden();
        $this->postJson(route('admin.roles.store'), $this->validPayload())->assertForbidden();

        $this->actingAsAdmin([AdminPermission::RolesView]);
        $this->get(route('admin.roles.index'))->assertOk();
        $this->postJson(route('admin.roles.store'), $this->validPayload())->assertForbidden();
        $this->deleteJson(route('admin.roles.destroy', $role), ['reason' => 'Not needed anymore'])->assertForbidden();
    }

    public function test_index_has_a_collapsed_filters_card(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesView]);

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('data-filters-for="'.RolesDataTable::TABLE_ID.'"', false)
            ->assertSee('aria-expanded="false"', false);
    }

    public function test_datatable_lists_roles_with_counts_and_respects_filters(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesView]);
        $custom = $this->customRole('night_shift', ['ar' => 'وردية الليل', 'en' => 'Night shift'], [AdminPermission::BookingsView, AdminPermission::OffersView]);
        Admin::factory()->create()->assignRole($custom);
        $url = route('admin.roles.index');

        $this->datatable($url)->assertOk()->assertJsonPath('recordsTotal', count(AdminRole::cases()) + 1);

        $response = $this->datatable($url, ['search' => 'Night']);
        $response->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.permissions_count', 2)->assertJsonPath('data.0.admins_count', 1);

        $this->datatable($url, ['search' => 'night_shift'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['system' => '0'])->assertJsonPath('recordsFiltered', 1);
        $this->datatable($url, ['system' => '1'])->assertJsonPath('recordsFiltered', count(AdminRole::cases()));
    }

    public function test_precognitive_store_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesManage]);

        $this->precognitive('POST', route('admin.roles.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'label.ar']);
        $this->precognitive('POST', route('admin.roles.store'), $this->validPayload(['name' => 'Bad Name', 'permissions' => ['ghost.permission']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'permissions.0']);
        $this->precognitive('POST', route('admin.roles.store'), $this->validPayload())->assertNoContent();

        $this->assertDatabaseMissing('roles', ['name' => 'night_shift']);
    }

    public function test_store_creates_the_role_with_permissions_and_audits_them(): void
    {
        $actor = $this->actingAsAdmin([AdminPermission::RolesManage, AdminPermission::BookingsView, AdminPermission::OffersView]);

        $response = $this->postJson(route('admin.roles.store'), $this->validPayload(['permissions' => [AdminPermission::BookingsView->value, AdminPermission::OffersView->value]]))
            ->assertOk();

        $role = Role::query()->where('name', 'night_shift')->sole();
        $response->assertJsonPath('data.redirect', route('admin.roles.show', $role));
        $this->assertFalse($role->is_system);
        $this->assertSame('admin', $role->guard_name);
        $this->assertEqualsCanonicalizing(['bookings.view', 'offers.view'], $role->permissions->pluck('name')->all());

        $log = AdminAuditLog::query()->where('action', AuditAction::Created->value)->where('auditable_type', 'role')->sole();
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertEqualsCanonicalizing(['bookings.view', 'offers.view'], $log->after['permissions']);
    }

    public function test_update_records_the_permission_diff_and_checks_the_lock_version(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesManage, AdminPermission::BookingsView, AdminPermission::OffersView, AdminPermission::CouponsView]);
        $role = $this->customRole('night_shift', ['ar' => 'وردية', 'en' => 'Night'], [AdminPermission::BookingsView]);

        $payload = [
            'label' => ['ar' => 'وردية جديدة', 'en' => 'New label'],
            'permissions' => [AdminPermission::OffersView->value, AdminPermission::CouponsView->value],
            'lock_version' => 0,
        ];

        $this->putJson(route('admin.roles.update', $role), $payload + ['name' => 'night_shift'])->assertOk();

        $role->refresh();
        $this->assertSame('New label', $role->getTranslation('label', 'en'));
        $this->assertSame(1, $role->lock_version);
        $this->assertEqualsCanonicalizing(['offers.view', 'coupons.view'], $role->permissions->pluck('name')->all());

        $log = AdminAuditLog::query()->where('action', AuditAction::Updated->value)->where('auditable_type', 'role')->sole();
        $this->assertSame(['bookings.view'], $log->before['permissions']);
        $this->assertEqualsCanonicalizing(['coupons.view', 'offers.view'], $log->after['permissions']);

        $this->putJson(route('admin.roles.update', $role), $payload + ['name' => 'night_shift'])
            ->assertStatus(409)
            ->assertJsonPath('errors._code', 'stale_version');
    }

    public function test_permission_changes_take_effect_for_holders_immediately(): void
    {
        $manager = $this->actingAsAdmin([AdminPermission::RolesManage]);
        $manager->assignRole(AdminRole::SuperAdmin->value);
        $holder = Admin::factory()->create();
        $role = $this->customRole('night_shift', ['ar' => 'وردية', 'en' => 'Night'], [AdminPermission::BookingsView]);
        $holder->assignRole($role);

        $this->assertTrue($holder->fresh()->can('bookings.view'));

        $this->putJson(route('admin.roles.update', $role), ['name' => 'night_shift', 'label' => ['ar' => 'وردية'], 'permissions' => [], 'lock_version' => 0])->assertOk();

        $this->assertFalse($holder->fresh()->can('bookings.view'));
    }

    public function test_system_roles_keep_their_identifier_and_cannot_be_deleted(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesManage, AdminPermission::DashboardView]);
        $system = Role::query()->where('name', AdminRole::DispatchAgent->value)->sole();

        $this->deleteJson(route('admin.roles.destroy', $system), ['reason' => 'No longer wanted'])->assertForbidden();
        $this->assertModelExists($system);

        // The identifier is not editable for system roles.
        $this->putJson(route('admin.roles.update', $system), ['name' => 'renamed', 'label' => ['ar' => 'س'], 'permissions' => [], 'lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->putJson(route('admin.roles.update', $system), ['label' => ['ar' => 'موزع', 'en' => 'Dispatcher'], 'permissions' => [AdminPermission::DashboardView->value], 'lock_version' => 0])->assertOk();
        $this->assertSame(AdminRole::DispatchAgent->value, $system->refresh()->name);
        $this->assertSame(['dashboard.view'], $system->permissions->pluck('name')->all());
    }

    public function test_the_super_admin_role_cannot_be_edited_or_deleted(): void
    {
        $actor = $this->actingAsAdmin([AdminPermission::RolesManage]);
        $actor->assignRole(AdminRole::SuperAdmin->value);
        $super = Role::query()->where('name', AdminRole::SuperAdmin->value)->sole();

        // Policies are bypassed for Super Admin by Gate::before, so the service enforces the protection.
        $this->putJson(route('admin.roles.update', $super), ['label' => ['ar' => 'س'], 'permissions' => [], 'lock_version' => 0])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'role_super_admin_protected');
        $this->deleteJson(route('admin.roles.destroy', $super), ['reason' => 'No longer wanted'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'role_system_protected');
    }

    public function test_deleting_a_custom_role_needs_a_reason_and_an_unused_role(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesManage]);
        $role = $this->customRole();
        $holder = Admin::factory()->create();
        $holder->assignRole($role);

        $this->deleteJson(route('admin.roles.destroy', $role), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->deleteJson(route('admin.roles.destroy', $role), ['reason' => 'Retired the night shift'])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'role_in_use');
        $this->assertModelExists($role);

        $holder->removeRole($role);
        $this->deleteJson(route('admin.roles.destroy', $role), ['reason' => 'Retired the night shift'])
            ->assertOk()
            ->assertJsonPath('data.reload_table', [RolesDataTable::TABLE_ID]);

        $this->assertModelMissing($role);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Deleted->value, 'auditable_type' => 'role', 'reason' => 'Retired the night shift']);
    }

    public function test_a_role_manager_cannot_grant_permissions_they_do_not_hold(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesManage, AdminPermission::BookingsView]);

        $this->postJson(route('admin.roles.store'), $this->validPayload(['permissions' => [AdminPermission::WalletsAdjust->value]]))
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'role_exceeds_own_permissions');
        $this->assertDatabaseMissing('roles', ['name' => 'night_shift']);

        // Keeping a permission the role already has is fine even if the editor lacks it.
        $role = $this->customRole('keeps', ['ar' => 'ك', 'en' => 'K'], [AdminPermission::WalletsView]);
        $this->putJson(route('admin.roles.update', $role), ['name' => 'keeps', 'label' => ['ar' => 'ك'], 'permissions' => [AdminPermission::WalletsView->value, AdminPermission::BookingsView->value], 'lock_version' => 0])->assertOk();
    }

    public function test_show_create_and_edit_pages_render_the_permission_matrix(): void
    {
        $this->actingAsAdmin([AdminPermission::RolesView, AdminPermission::RolesManage]);
        $role = $this->customRole('night_shift', ['ar' => 'وردية', 'en' => 'Night'], [AdminPermission::BookingsView]);

        $this->get(route('admin.roles.create'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee('name="permissions[]"', false)
            ->assertSee('toggleAll()', false)
            ->assertSee('name="label[ar]"', false);

        $this->get(route('admin.roles.edit', $role), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee('value="bookings.view"', false)
            ->assertSee('name="lock_version"', false);

        $this->get(route('admin.roles.show', $role), ['Accept-Language' => 'en'])->assertOk()->assertSee('Night');
        $this->get(route('admin.roles.activity', $role))->assertOk();
    }

    /**
     * @param array{ar: string, en: string} $label
     * @param list<AdminPermission> $permissions
     */
    private function customRole(string $name = 'night_shift', array $label = ['ar' => 'وردية الليل', 'en' => 'Night shift'], array $permissions = []): Role
    {
        $role = new Role(['name' => $name, 'guard_name' => 'admin', 'label' => $label, 'is_system' => false]);
        $role->save();
        $role->syncPermissions(array_map(static fn (AdminPermission $permission): string => $permission->value, $permissions));

        return $role;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'night_shift',
            'label' => ['ar' => 'وردية الليل', 'en' => 'Night shift'],
            'permissions' => [],
        ], $overrides);
    }
}
