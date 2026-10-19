<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminSession;
use App\Notifications\Admin\AdminPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\InteractsWithAdmin;
use Tests\Concerns\SignsInAdmins;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;
    use SignsInAdmins;

    public function test_forgot_password_answers_the_same_for_known_and_unknown_emails_and_only_mails_real_admins(): void
    {
        Notification::fake();
        $admin = $this->adminWithPassword();

        $known = $this->postJson(route('admin.password.email'), ['email' => $admin->email])->assertOk()->json('message');
        $unknown = $this->postJson(route('admin.password.email'), ['email' => 'ghost@example.test'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTo($admin, AdminPasswordNotification::class);
        Notification::assertSentTimes(AdminPasswordNotification::class, 1);
    }

    public function test_the_reset_form_renders_with_its_token_and_validates_before_submit(): void
    {
        $this->get(route('admin.password.reset', ['token' => 'abc', 'email' => 'a@example.test']), ['Accept-Language' => 'ar'])
            ->assertOk()
            ->assertSee('data-ajax-form', false)
            ->assertSee('name="token"', false)
            ->assertSee('value="a@example.test"', false);

        $this->precognitive('POST', route('admin.password.update'), ['token' => 't', 'email' => 'a@example.test', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_resetting_with_a_valid_token_changes_the_password_ends_sessions_and_is_audited(): void
    {
        $admin = $this->adminWithPassword();
        $token = Password::broker('admins')->createToken($admin);
        AdminSession::query()->create(['admin_id' => $admin->id, 'session_id' => 'old-session', 'last_active_at' => now()]);

        $this->postJson(route('admin.password.update'), [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'N3w-Secure-Passw0rd!',
            'password_confirmation' => 'N3w-Secure-Passw0rd!',
        ])->assertOk()->assertJsonPath('data.redirect', route('admin.login'));

        $this->assertTrue(Hash::check('N3w-Secure-Passw0rd!', $admin->refresh()->password));
        $this->assertSame(0, AdminSession::query()->where('admin_id', $admin->id)->count());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::PasswordChanged->value, 'auditable_id' => (string) $admin->id]);

        // The token is single use.
        $this->postJson(route('admin.password.update'), [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'An0ther-Secure-Passw0rd!',
            'password_confirmation' => 'An0ther-Secure-Passw0rd!',
        ])->assertStatus(422)->assertJsonPath('errors._code', 'password_reset_invalid');
    }

    public function test_an_invalid_token_is_rejected_and_weak_passwords_are_refused(): void
    {
        $admin = $this->adminWithPassword();

        $this->postJson(route('admin.password.update'), [
            'token' => 'nope', 'email' => $admin->email,
            'password' => 'N3w-Secure-Passw0rd!', 'password_confirmation' => 'N3w-Secure-Passw0rd!',
        ])->assertStatus(422)->assertJsonPath('errors._code', 'password_reset_invalid');

        foreach (['alllowercase1!x', 'NoDigitsOrSymbols', 'Sh0rt!'] as $weak) {
            $this->postJson(route('admin.password.update'), [
                'token' => 'nope', 'email' => $admin->email, 'password' => $weak, 'password_confirmation' => $weak,
            ])->assertStatus(422)->assertJsonValidationErrors(['password']);
        }

        $this->assertFalse(Hash::check('N3w-Secure-Passw0rd!', $admin->refresh()->password));
    }

    public function test_the_invitation_email_links_to_the_reset_form_in_the_admins_language(): void
    {
        $admin = Admin::factory()->create(['locale' => 'ar']);
        $notification = new AdminPasswordNotification('tok123', invited: true);

        $mail = $notification->toMail($admin);

        $this->assertStringContainsString(route('admin.password.reset', ['token' => 'tok123', 'email' => $admin->email]), $mail->actionUrl);
        $this->assertSame(__('admin.auth.mail.invitation.subject'), $mail->subject);
        $this->assertSame('ar', $admin->preferredLocale());
    }
}
