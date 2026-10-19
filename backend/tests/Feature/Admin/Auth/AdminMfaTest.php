<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\Admin\AdminLoginOutcome;
use App\Enums\Admin\AdminRole;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use Database\Seeders\RoleSeeder;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\Concerns\SignsInAdmins;
use Tests\Support\AdminTotp;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;
    use SignsInAdmins;

    public function test_with_mfa_enabled_the_password_alone_does_not_sign_in(): void
    {
        $admin = $this->adminWithPassword();
        AdminTotp::enable($admin);

        $this->submitLogin($admin->email)
            ->assertOk()
            ->assertJsonPath('data.redirect', route('admin.mfa.challenge'));

        $this->assertGuest('admin');
        $this->assertDatabaseMissing('admin_login_events', ['admin_id' => $admin->id, 'outcome' => AdminLoginOutcome::Success->value]);
    }

    public function test_a_valid_totp_code_completes_the_login_and_cannot_be_replayed(): void
    {
        $admin = $this->adminWithPassword();
        $secret = AdminTotp::enable($admin);
        $this->submitLogin($admin->email);

        $this->keepSession()->get(route('admin.mfa.challenge'))->assertOk()->assertSee('name="code"', false);

        $code = AdminTotp::code($secret);
        $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.redirect', route('admin.home'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertDatabaseHas('admin_login_events', ['admin_id' => $admin->id, 'outcome' => AdminLoginOutcome::Success->value]);

        // The same time step cannot be used twice (replay protection).
        $this->app['auth']->guard('admin')->logout();
        $this->flushSession();
        $this->submitLogin($admin->email);
        $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => $code])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
        $this->assertGuest('admin');
    }

    public function test_wrong_codes_are_recorded_and_five_failures_end_the_pending_login(): void
    {
        $admin = $this->adminWithPassword();
        AdminTotp::enable($admin);
        $this->submitLogin($admin->email);

        for ($i = 0; $i < 4; $i++) {
            $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => '000000'])
                ->assertStatus(422)
                ->assertJsonPath('errors._code', 'mfa_invalid_code');
        }

        $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => '000000'])->assertStatus(422);
        // The pending login is gone: even a correct code now fails.
        $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => AdminTotp::code((string) $admin->refresh()->mfa_secret)])->assertStatus(422);

        $this->assertGuest('admin');
        $this->assertSame(5, $admin->loginEvents()->where('outcome', AdminLoginOutcome::MfaFailed->value)->count());
    }

    public function test_a_recovery_code_works_exactly_once(): void
    {
        $admin = $this->adminWithPassword();
        AdminTotp::enable($admin, ['abcde-12345', 'fghij-67890']);

        $this->submitLogin($admin->email);
        $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => 'ABCDE-12345'])->assertOk();
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertCount(1, $admin->refresh()->mfa_recovery_codes ?? []);

        $this->app['auth']->guard('admin')->logout();
        $this->flushSession();
        $this->submitLogin($admin->email);
        $this->keepSession()->postJson(route('admin.mfa.verify'), ['code' => 'abcde-12345'])->assertStatus(422);
        $this->assertGuest('admin');
    }

    public function test_the_challenge_page_needs_a_pending_login_and_the_code_format_is_validated(): void
    {
        $this->get(route('admin.mfa.challenge'))->assertRedirect(route('admin.login'));

        $this->precognitive('POST', route('admin.mfa.verify'), ['code' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
        $this->precognitive('POST', route('admin.mfa.verify'), ['code' => '123456'])->assertNoContent();
    }

    public function test_enrolment_shows_a_qr_code_and_confirms_with_a_valid_code_then_shows_recovery_codes_once(): void
    {
        $admin = $this->actingAsAdmin();

        $page = $this->get(route('admin.security.mfa'))->assertOk()->assertSee('<svg', false)->assertSee('name="code"', false);
        $this->assertFalse($admin->refresh()->hasMfaEnabled());

        $secret = (string) session('admin.mfa.setup_secret');
        $this->assertNotSame('', $secret);
        $page->assertSee(substr($secret, 0, 4));

        $this->precognitive('POST', route('admin.security.mfa.confirm'), ['code' => 'abc'])->assertStatus(422);

        $this->keepSession()->postJson(route('admin.security.mfa.confirm'), ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
        $this->assertFalse($admin->refresh()->hasMfaEnabled());

        $this->keepSession()->postJson(route('admin.security.mfa.confirm'), ['code' => AdminTotp::code($secret)])
            ->assertOk()
            ->assertJsonPath('data.redirect', route('admin.security.mfa.recovery'));

        $admin->refresh();
        $this->assertTrue($admin->hasMfaEnabled());
        $this->assertSame($secret, $admin->mfa_secret);
        $this->assertCount(8, $admin->mfa_recovery_codes ?? []);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::MfaEnabled->value, 'auditable_id' => (string) $admin->id]);

        // The secret is encrypted at rest and never part of the audit snapshot.
        $this->assertNotSame($secret, DB::table('admins')->where('id', $admin->id)->value('mfa_secret'));
        $this->assertStringNotContainsString($secret, (string) json_encode(\App\Models\AdminAuditLog::query()->pluck('after')));

        $this->keepSession()->get(route('admin.security.mfa.recovery'))->assertOk()->assertSee('font-mono', false);
        // Codes are flashed: a reload no longer shows them.
        $this->keepSession()->get(route('admin.security.mfa.recovery'))->assertRedirect(route('admin.security.index'));
    }

    public function test_roles_that_require_mfa_must_enrol_before_using_the_panel(): void
    {
        config(['admin.auth.mfa.required_roles' => [AdminRole::FinanceManager->value]]);
        $this->seed(RoleSeeder::class);

        $admin = $this->actingAsAdmin();
        $admin->assignRole(AdminRole::FinanceManager->value);

        $this->get(route('admin.home'))->assertRedirect(route('admin.security.mfa'));
        $this->getJson(route('admin.dashboard'))->assertForbidden()->assertJsonPath('errors._code', 'mfa_enrolment_required');
        // The security screens and logout stay reachable.
        $this->get(route('admin.security.index'))->assertOk();
        $this->get(route('admin.security.mfa'))->assertOk();

        AdminTotp::enable($admin);
        // Enrolled: the panel opens (this role has no module yet, so the empty home page shows).
        $this->get(route('admin.home'))->assertOk();
    }

    public function test_mfa_can_be_turned_off_with_the_password_unless_a_role_requires_it(): void
    {
        $admin = $this->actingAsAdmin();
        AdminTotp::enable($admin);

        $this->postJson(route('admin.security.mfa.disable'), ['password' => 'wrong'])->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->assertTrue($admin->refresh()->hasMfaEnabled());

        config(['admin.auth.mfa.required_roles' => ['support_role']]);
        $this->seed(RoleSeeder::class);
        $admin->assignRole(AdminRole::SuperAdmin->value);
        config(['admin.auth.mfa.required_roles' => [AdminRole::SuperAdmin->value]]);
        $this->postJson(route('admin.security.mfa.disable'), ['password' => 'Str0ng-Password!'])->assertStatus(422);
        $this->assertTrue($admin->refresh()->hasMfaEnabled());

        config(['admin.auth.mfa.required_roles' => []]);
        $this->postJson(route('admin.security.mfa.disable'), ['password' => 'Str0ng-Password!'])->assertOk();
        $this->assertFalse($admin->refresh()->hasMfaEnabled());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::MfaDisabled->value]);
    }

    public function test_recovery_codes_can_be_regenerated_with_the_password(): void
    {
        $admin = $this->actingAsAdmin();
        AdminTotp::enable($admin, ['aaaaa-11111']);
        $old = $admin->refresh()->mfa_recovery_codes;

        $this->postJson(route('admin.security.mfa.regenerate'), ['password' => 'wrong'])->assertStatus(422);
        $this->postJson(route('admin.security.mfa.regenerate'), ['password' => 'Str0ng-Password!'])
            ->assertOk()
            ->assertJsonPath('data.redirect', route('admin.security.mfa.recovery'));

        $this->assertNotSame($old, $admin->refresh()->mfa_recovery_codes);
        $this->assertCount(8, $admin->mfa_recovery_codes ?? []);
    }

    public function test_another_admin_resets_mfa_with_a_mandatory_reason(): void
    {
        $actor = $this->actingAsAdmin([\App\Enums\Admin\AdminPermission::AdminsManage]);
        $target = Admin::factory()->create();
        AdminTotp::enable($target);

        $this->postJson(route('admin.admins.reset-mfa', $target), [])->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->assertTrue($target->refresh()->hasMfaEnabled());

        $this->postJson(route('admin.admins.reset-mfa', $target), ['reason' => 'Lost the phone'])->assertOk();

        $this->assertFalse($target->refresh()->hasMfaEnabled());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::MfaReset->value, 'auditable_id' => (string) $target->id, 'reason' => 'Lost the phone', 'actor_id' => $actor->id]);

        // Not on yourself (use the security page), and not when nothing is enrolled.
        AdminTotp::enable($actor);
        $this->postJson(route('admin.admins.reset-mfa', $actor), ['reason' => 'Lost the phone'])->assertForbidden();
    }
}
