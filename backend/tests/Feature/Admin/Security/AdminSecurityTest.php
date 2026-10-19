<?php

namespace Tests\Feature\Admin\Security;

use App\Enums\Common\AuditAction;
use App\Models\Admin;
use App\Models\AdminSession;
use App\Services\Admin\AdminSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithAdmin;
use Tests\Concerns\SignsInAdmins;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;
    use SignsInAdmins;

    public function test_the_account_page_needs_no_permission_and_lists_sessions(): void
    {
        $admin = $this->actingAsAdmin();
        app(AdminSessionService::class)->start($admin, 'other-browser-session');

        $this->get(route('admin.security.index'), ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertSee('data-ajax-form', false)
            ->assertSee('This device')
            ->assertSee('data-confirm-action', false);
    }

    public function test_the_language_choice_is_persisted_on_the_admin_and_wins_over_the_browser(): void
    {
        $admin = $this->actingAsAdmin();
        $this->assertNull($admin->locale);

        $this->postJson(route('admin.locale.update'), ['locale' => 'en'], ['Accept-Language' => 'ar'])->assertOk();
        $this->assertSame('en', $admin->refresh()->locale);

        // A later request from a browser that prefers Arabic still renders English: the stored preference wins.
        $this->get(route('admin.security.index'), ['Accept-Language' => 'ar'])->assertOk()->assertSee('dir="ltr"', false);

        $this->postJson(route('admin.locale.update'), ['locale' => 'ar'])->assertOk();
        $this->assertSame('ar', $admin->refresh()->locale);
        $this->get(route('admin.security.index'), ['Accept-Language' => 'en'])->assertOk()->assertSee('dir="rtl"', false);
    }

    public function test_the_locale_switcher_works_for_guests_through_the_session(): void
    {
        $this->postJson(route('admin.locale.update'), ['locale' => 'en'])->assertOk();
        $this->precognitive('POST', route('admin.locale.update'), ['locale' => 'xx'])->assertStatus(422);
    }

    public function test_profile_update_validates_persists_and_is_audited(): void
    {
        $admin = $this->actingAsAdmin();

        $this->precognitive('PUT', route('admin.security.profile.update'), ['name' => '', 'locale' => 'xx'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'locale']);
        $this->precognitive('PUT', route('admin.security.profile.update'), ['name' => 'New Name', 'locale' => 'en'])->assertNoContent();
        $this->assertNotSame('New Name', $admin->refresh()->name);

        $this->putJson(route('admin.security.profile.update'), ['name' => 'New Name', 'phone' => '+201000000000', 'locale' => 'en'])->assertOk();

        $this->assertSame('New Name', $admin->refresh()->name);
        $this->assertSame('en', $admin->locale);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::Updated->value, 'auditable_id' => (string) $admin->id, 'actor_id' => $admin->id]);
    }

    public function test_changing_the_password_needs_the_current_one_and_ends_other_sessions(): void
    {
        $admin = $this->actingAsAdmin();
        AdminSession::query()->create(['admin_id' => $admin->id, 'session_id' => 'other-browser', 'last_active_at' => now()]);

        $this->putJson(route('admin.security.password.update'), [
            'current_password' => 'wrong', 'password' => 'N3w-Secure-Passw0rd!', 'password_confirmation' => 'N3w-Secure-Passw0rd!',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->putJson(route('admin.security.password.update'), [
            'current_password' => 'Str0ng-Password!', 'password' => 'Str0ng-Password!', 'password_confirmation' => 'Str0ng-Password!',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->putJson(route('admin.security.password.update'), [
            'current_password' => 'Str0ng-Password!', 'password' => 'N3w-Secure-Passw0rd!', 'password_confirmation' => 'N3w-Secure-Passw0rd!',
        ])->assertOk();

        $this->assertTrue(Hash::check('N3w-Secure-Passw0rd!', $admin->refresh()->password));
        $this->assertSame(0, AdminSession::query()->where('session_id', 'other-browser')->count());
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::PasswordChanged->value, 'auditable_id' => (string) $admin->id]);
    }

    public function test_an_admin_can_revoke_their_own_other_sessions_but_not_someone_elses(): void
    {
        $admin = $this->actingAsAdmin();
        $mine = AdminSession::query()->create(['admin_id' => $admin->id, 'session_id' => 'mine-other', 'last_active_at' => now()]);
        $theirs = AdminSession::query()->create(['admin_id' => Admin::factory()->create()->id, 'session_id' => 'theirs', 'last_active_at' => now()]);

        $this->deleteJson(route('admin.security.sessions.destroy', $theirs->id))->assertNotFound();
        $this->assertDatabaseHas('admin_sessions', ['id' => $theirs->id]);

        $this->deleteJson(route('admin.security.sessions.destroy', $mine->id))->assertOk();
        $this->assertDatabaseMissing('admin_sessions', ['id' => $mine->id]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => AuditAction::SessionRevoked->value, 'auditable_id' => (string) $admin->id]);
    }

    public function test_the_current_session_cannot_be_revoked_from_the_list_and_others_can_be_revoked_in_bulk(): void
    {
        $admin = $this->actingAsAdmin();
        $this->get(route('admin.security.index'))->assertOk();
        $current = AdminSession::query()->where('admin_id', $admin->id)->sole();
        AdminSession::query()->create(['admin_id' => $admin->id, 'session_id' => 'a', 'last_active_at' => now()]);
        AdminSession::query()->create(['admin_id' => $admin->id, 'session_id' => 'b', 'last_active_at' => now()]);

        $this->keepSession()->deleteJson(route('admin.security.sessions.destroy', $current->id))->assertNotFound();
        $this->keepSession()->deleteJson(route('admin.security.sessions.destroy-others'))->assertOk();

        $this->assertSame([$current->session_id], AdminSession::query()->where('admin_id', $admin->id)->pluck('session_id')->all());
    }
}
