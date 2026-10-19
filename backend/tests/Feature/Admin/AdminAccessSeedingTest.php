<?php

namespace Tests\Feature\Admin;

use App\Enums\Admin\AdminPermission;
use App\Enums\Admin\AdminRole;
use App\Enums\Common\ActorType;
use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminAccessSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_permission_seeder_syncs_the_enum_to_the_admin_guard_idempotently(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(PermissionSeeder::class);

        $names = Permission::query()->where('guard_name', 'admin')->orderBy('name')->pluck('name')->all();
        $expected = AdminPermission::values();
        sort($expected);

        $this->assertSame($expected, $names);
        $this->assertSame(0, Permission::query()->where('guard_name', '!=', 'admin')->count());
    }

    public function test_the_permission_seeder_removes_permissions_that_left_the_enum(): void
    {
        Permission::findOrCreate('retired.permission', 'admin');
        Permission::findOrCreate('web.permission', 'web');

        $this->seed(PermissionSeeder::class);

        $this->assertDatabaseMissing('permissions', ['name' => 'retired.permission']);
        $this->assertDatabaseHas('permissions', ['name' => 'web.permission', 'guard_name' => 'web']);
    }

    public function test_the_role_seeder_creates_the_twelve_system_roles_with_their_default_permissions(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertSame(12, Role::query()->where('is_system', true)->count());

        foreach (AdminRole::cases() as $case) {
            $role = Role::query()->where('name', $case->value)->where('guard_name', 'admin')->sole();
            $expected = array_map(static fn (AdminPermission $permission): string => $permission->value, $case->defaultPermissions());
            sort($expected);

            $this->assertSame($expected, $role->permissions->pluck('name')->sort()->values()->all(), $case->value);
            $this->assertNotSame('', $role->getTranslation('label', 'ar'));
            $this->assertNotSame('', $role->getTranslation('label', 'en'));
        }
    }

    public function test_appendix_b_examples_hold(): void
    {
        $this->seed(RoleSeeder::class);
        $permissions = fn (AdminRole $role): array => Role::query()->where('name', $role->value)->sole()->permissions->pluck('name')->all();

        $this->assertSame([], $permissions(AdminRole::SuperAdmin));
        $this->assertEqualsCanonicalizing(['payouts.view', 'payouts.review', 'wallets.view', 'ledger.view'], $permissions(AdminRole::FinancePayoutOfficer));
        $this->assertEqualsCanonicalizing(['content.manage', 'translations.manage', 'leads.view'], $permissions(AdminRole::ContentCmsEditor));
        $this->assertContains('wallets.adjust', $permissions(AdminRole::FinanceManager));
        $this->assertContains('wallets.approve_adjustment', $permissions(AdminRole::FinanceManager));
        $this->assertNotContains('content.publish', $permissions(AdminRole::ContentCmsEditor));

        $auditor = $permissions(AdminRole::ReadOnlyAuditor);
        $this->assertContains('audit_logs.view', $auditor);
        $this->assertContains('dashboard.view', $auditor);
        $this->assertSame([], array_values(array_filter($auditor, static fn (string $name): bool => ! str_ends_with($name, '.view'))));
        $this->assertSame([], array_values(array_filter($auditor, static fn (string $name): bool => str_contains($name, 'view_pii'))));
    }

    public function test_reseeding_keeps_permissions_edited_in_the_panel(): void
    {
        $this->seed(RoleSeeder::class);
        $role = Role::query()->where('name', AdminRole::DispatchAgent->value)->sole();
        $role->syncPermissions([AdminPermission::BookingsView->value]);
        $role->setTranslation('label', 'en', 'Custom label')->save();

        $this->seed(RoleSeeder::class);

        $role->refresh();
        $this->assertSame(['bookings.view'], $role->permissions->pluck('name')->all());
        $this->assertSame('Custom label', $role->getTranslation('label', 'en'));
        $this->assertSame(12, Role::query()->count());
    }

    public function test_super_admin_passes_every_ability_through_gate_before_and_others_only_what_they_hold(): void
    {
        $this->seed(RoleSeeder::class);
        $super = Admin::factory()->create();
        $super->assignRole(AdminRole::SuperAdmin->value);
        $agent = Admin::factory()->create();
        $agent->assignRole(AdminRole::DispatchAgent->value);

        $this->assertTrue(Gate::forUser($super)->allows(AdminPermission::WalletsAdjust->value));
        $this->assertTrue(Gate::forUser($super)->allows('anything.at_all'));
        $this->assertTrue(Gate::forUser($agent)->allows(AdminPermission::BookingsManage->value));
        $this->assertFalse(Gate::forUser($agent)->allows(AdminPermission::WalletsAdjust->value));
        $this->assertFalse(Gate::forUser(Admin::factory()->create())->allows(AdminPermission::BookingsView->value));
    }

    public function test_the_admin_model_is_in_the_morph_map_and_stored_as_a_stable_alias(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = Admin::factory()->create();
        $admin->assignRole(AdminRole::SuperAdmin->value);

        $this->assertSame(Admin::class, Relation::getMorphedModel('admin'));
        $this->assertSame(Role::class, Relation::getMorphedModel('role'));
        $this->assertSame('admin', $admin->getMorphClass());
        $this->assertSame(ActorType::Admin->value, $admin->getMorphClass());
        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'admin', 'model_id' => $admin->id]);
    }

    public function test_every_permission_and_role_has_arabic_and_english_labels(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach (AdminPermission::cases() as $permission) {
                $this->assertStringNotContainsString('enums.admin_permission', $permission->label(), "{$locale}: {$permission->value}");
            }

            foreach (AdminRole::cases() as $role) {
                $this->assertNotSame("enums.admin_role.{$role->value}", $role->label(), "{$locale}: {$role->value}");
            }
        }
    }

    public function test_local_guest_access_is_gone(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Middleware/Admin/AllowLocalGuestAccess.php'));
        $this->assertNull(config('admin.local_guest_access'));
        $this->assertStringNotContainsString('ADMIN_LOCAL_GUEST_ACCESS', (string) file_get_contents(base_path('.env.example')));
    }
}
