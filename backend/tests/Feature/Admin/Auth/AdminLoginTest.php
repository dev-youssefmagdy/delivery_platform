<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\Admin\AdminLoginOutcome;
use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\AdminLoginEvent;
use App\Models\AdminSession;
use App\Services\Admin\AdminSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\InteractsWithAdmin;
use Tests\Concerns\SignsInAdmins;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;
    use SignsInAdmins;

    public function test_guests_are_redirected_to_the_login_page_and_it_renders_in_arabic_by_default(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.home')));
        $this->assertGuestBlocked($this->get(route('admin.dashboard')));

        $this->get(route('admin.login'), ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('data-ajax-form', false)
            ->assertSee('name="email"', false)
            ->assertSee(__('admin.auth.login.title'));
    }

    public function test_json_requests_from_guests_get_a_401_envelope(): void
    {
        $this->getJson(route('admin.dashboard'))->assertUnauthorized()->assertJsonPath('errors._code', 'unauthenticated');
    }

    public function test_precognitive_login_validates_the_shape_without_signing_in(): void
    {
        $this->precognitive('POST', route('admin.login.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $admin = $this->adminWithPassword();
        $this->precognitive('POST', route('admin.login.store'), ['email' => $admin->email, 'password' => 'whatever'])
            ->assertNoContent();

        $this->assertGuest('admin');
        $this->assertSame(0, AdminLoginEvent::query()->count());
    }

    public function test_successful_login_signs_in_records_activity_audit_and_a_tracked_session(): void
    {
        $admin = $this->adminWithPassword();

        $this->submitLogin($admin->email)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', route('admin.home'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNotNull($admin->refresh()->last_login_at);
        $this->assertDatabaseHas('admin_login_events', ['admin_id' => $admin->id, 'outcome' => AdminLoginOutcome::Success->value]);
        $this->assertDatabaseHas('admin_audit_logs', ['actor_type' => 'admin', 'actor_id' => $admin->id, 'action' => AuditAction::LoggedIn->value]);
        $this->assertSame(1, AdminSession::query()->where('admin_id', $admin->id)->count());
    }

    public function test_the_error_is_identical_for_unknown_email_wrong_password_and_suspended_accounts(): void
    {
        $active = $this->adminWithPassword();
        $suspended = Admin::factory()->suspended()->create();

        $messages = [
            $this->submitLogin('nobody@example.test')->assertStatus(422)->assertJsonValidationErrors(['email'])->json('errors.email.0'),
            $this->submitLogin($active->email, 'wrong-Password-1!')->assertStatus(422)->json('errors.email.0'),
            $this->submitLogin($suspended->email)->assertStatus(422)->json('errors.email.0'),
        ];

        $this->assertCount(1, array_unique($messages));
        $this->assertGuest('admin');
        $this->assertDatabaseHas('admin_login_events', ['email' => $suspended->email, 'outcome' => AdminLoginOutcome::AccountSuspended->value]);
        $this->assertDatabaseHas('admin_login_events', ['email' => 'nobody@example.test', 'outcome' => AdminLoginOutcome::InvalidCredentials->value]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::LoginFailed->value]);
    }

    public function test_failed_logins_are_throttled_per_email_and_ip_but_validation_calls_are_not_counted(): void
    {
        RateLimiter::clear('admin-login:'.'someone@example.test|127.0.0.1');
        $admin = $this->adminWithPassword();

        for ($i = 0; $i < 12; $i++) {
            $this->precognitive('POST', route('admin.login.store'), ['email' => $admin->email, 'password' => 'x'])->assertNoContent();
        }

        for ($i = 0; $i < 5; $i++) {
            $this->submitLogin($admin->email, 'wrong-Password-1!')->assertStatus(422);
        }

        $this->submitLogin($admin->email, 'wrong-Password-1!')->assertStatus(429)->assertJsonPath('errors._code', 'too_many_requests');
        // Even the right password is refused while locked out.
        $this->submitLogin($admin->email)->assertStatus(429);

        $this->assertGuest('admin');
        $this->assertDatabaseHas('admin_login_events', ['email' => $admin->email, 'outcome' => AdminLoginOutcome::Throttled->value]);
    }

    public function test_logout_ends_the_session_and_is_recorded(): void
    {
        $admin = $this->actingAsAdmin();

        $this->get(route('admin.security.index'))->assertOk();
        $this->keepSession()->postJson(route('admin.logout'))->assertOk()->assertJsonPath('data.redirect', route('admin.login'));

        $this->assertGuest('admin');
        $this->assertDatabaseHas('admin_login_events', ['admin_id' => $admin->id, 'outcome' => AdminLoginOutcome::Logout->value]);
        $this->assertSame(0, AdminSession::query()->where('admin_id', $admin->id)->count());
        $this->assertTrue(AdminAuditLog::query()->where('action', AuditAction::LoggedOut->value)->exists());
    }

    public function test_a_suspended_admin_is_signed_out_on_the_next_request(): void
    {
        $admin = $this->actingAsAdmin();
        $admin->forceFill(['status' => 'suspended'])->save();

        $this->get(route('admin.security.index'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_a_revoked_session_is_signed_out_on_the_next_request(): void
    {
        $admin = $this->actingAsAdmin();

        // A marked session (created by a real login) whose tracking row was removed has been revoked.
        $this->withSession([AdminSessionService::MARKER => true])
            ->get(route('admin.security.index'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest('admin');
        $this->assertTrue($admin->exists);
    }

    public function test_signed_in_admins_do_not_see_the_login_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.login'))->assertRedirect(route('admin.home'));
    }

    public function test_the_login_page_clears_a_half_finished_mfa_step(): void
    {
        $this->withSession(['admin.mfa.pending' => ['admin_id' => 1, 'expires_at' => now()->addMinute()->getTimestamp()]])
            ->get(route('admin.login'))
            ->assertOk();

        $this->assertNull(session('admin.mfa.pending'));
    }
}
