<?php

namespace Tests\Feature\Admin;

use App\Enums\Admin\AdminRole;
use App\Models\Admin;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_super_admin_interactively_with_no_default_credentials(): void
    {
        $this->seed(RoleSeeder::class);
        $this->assertSame(0, Admin::query()->count());

        $this->artisan('admin:create')
            ->expectsQuestion('Full name', 'First Admin')
            ->expectsQuestion('Email address', 'First@Example.test')
            ->expectsQuestion('Password', 'Str0ng-Password!')
            ->expectsQuestion('Confirm password', 'Str0ng-Password!')
            ->assertSuccessful();

        $admin = Admin::query()->sole();
        $this->assertSame('first@example.test', $admin->email);
        $this->assertTrue(Hash::check('Str0ng-Password!', $admin->password));
        $this->assertTrue($admin->hasRole(AdminRole::SuperAdmin->value));
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'created', 'auditable_type' => 'admin', 'actor_type' => 'system']);
    }

    public function test_it_refuses_non_interactive_use_unknown_roles_and_duplicate_emails(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('admin:create', ['--no-interaction' => true, '--email' => 'a@example.test', '--name' => 'A'])->assertFailed();
        $this->artisan('admin:create', ['--role' => 'ghost'])->assertFailed();

        Admin::factory()->create(['email' => 'taken@example.test']);
        $this->artisan('admin:create', ['--email' => 'taken@example.test', '--name' => 'Dup'])->assertFailed();
        $this->assertSame(1, Admin::query()->count());
    }

    public function test_it_rejects_a_mismatching_confirmation(): void
    {
        $this->seed(RoleSeeder::class);

        $this->artisan('admin:create', ['--email' => 'b@example.test', '--name' => 'B'])
            ->expectsQuestion('Password', 'Str0ng-Password!')
            ->expectsQuestion('Confirm password', 'Different-Passw0rd!')
            ->assertFailed();

        $this->assertSame(0, Admin::query()->count());
    }
}
