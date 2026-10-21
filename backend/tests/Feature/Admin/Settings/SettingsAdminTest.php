<?php

namespace Tests\Feature\Admin\Settings;

use App\Enums\Admin\AdminPermission;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingGroup;
use App\Enums\Settings\SettingKey;
use App\Services\Settings\SettingsService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

/**
 * Admin Module Contract (Plan §5.6) for the typed Settings screen.
 */
class SettingsAdminTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_guests_are_blocked(): void
    {
        $this->assertGuestBlocked($this->get(route('admin.settings.index')));
    }

    public function test_admin_without_permission_gets_403_on_page_and_json_write(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.settings.index'))->assertForbidden();
        $this->putJson(route('admin.settings.update', 'otp'), $this->otpPayload())->assertForbidden()->assertJsonPath('errors._code', 'forbidden');

        $this->actingAsAdmin([AdminPermission::SettingsView]);
        $this->putJson(route('admin.settings.update', 'otp'), $this->otpPayload())->assertForbidden();
    }

    public function test_page_renders_one_tab_per_group_with_fields_generated_from_the_enum(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);

        $response = $this->get(route('admin.settings.index'))->assertOk();

        foreach (SettingGroup::cases() as $group) {
            $response->assertSee('data-tab-trigger="'.$group->value.'"', false)
                ->assertSee(route('admin.settings.update', $group->value), false);
        }

        foreach (SettingKey::cases() as $key) {
            $response->assertSee($key->label());
        }

        $response->assertSee('name="settings[otp_length]"', false)
            ->assertSee('name="settings[supported_locales][]"', false)
            ->assertSee('name="settings[ride_pin_required]"', false);
    }

    public function test_viewers_without_manage_see_read_only_fields_and_no_form(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView]);

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('<fieldset disabled', false)
            ->assertSee(__('admin.settings.read_only'))
            ->assertDontSee(route('admin.settings.update', 'otp'), false);
    }

    public function test_the_active_tab_can_be_selected_by_query_string(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView]);

        $this->get(route('admin.settings.index', ['tab' => 'dispatch']))->assertOk()->assertSee("adminTabs('dispatch')", false);
    }

    public function test_precognitive_update_validates_without_persisting(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);

        $this->precognitive('PUT', route('admin.settings.update', 'otp'), ['settings' => ['otp_length' => '2', 'otp_ttl_seconds' => '300', 'otp_resend_cooldown_seconds' => '60', 'otp_max_attempts' => '5']])
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'validation_failed')
            ->assertJsonValidationErrors(['settings.otp_length']);

        $this->precognitive('PUT', route('admin.settings.update', 'otp'), $this->otpPayload())
            ->assertNoContent()
            ->assertHeader('Precognition-Success', 'true');

        $this->assertDatabaseCount('platform_settings', 0);
    }

    public function test_every_group_validates_its_own_fields(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);

        $invalid = [
            'localization' => ['settings' => ['default_country' => 'ZZ', 'default_locale' => 'fr', 'supported_locales' => ['fr'], 'default_currency' => 'XXX', 'digits_preference' => 'roman']],
            'support' => ['settings' => ['support_phone' => 'abc', 'support_email' => 'no', 'support_whatsapp' => 'x']],
            'dispatch' => ['settings' => ['dispatch_radius_steps_km' => '0, abc', 'dispatch_batch_size' => '0']],
            'apps' => ['settings' => ['min_app_version_customer' => '1.0', 'min_app_version_driver' => 'x']],
            'security' => ['settings' => ['admin_mfa_required_roles' => ['ghost'], 'admin_login_lockout_seconds' => '5']],
            'rate_limits' => ['settings' => ['rate_limit_api_per_minute' => '0']],
        ];

        foreach ($invalid as $group => $payload) {
            $this->precognitive('PUT', route('admin.settings.update', $group), $payload)->assertStatus(422);
        }

        $this->assertDatabaseCount('platform_settings', 0);
    }

    public function test_update_persists_audits_and_redirects_back_to_the_tab(): void
    {
        $admin = $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);
        $payload = $this->otpPayload(['otp_length' => '8']);

        $this->putJson(route('admin.settings.update', 'otp'), $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', route('admin.settings.index', ['tab' => 'otp']));

        $this->assertSame(8, app(SettingsService::class)->get(SettingKey::OtpLength));
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::Updated->value,
            'auditable_type' => 'platform_setting',
            'actor_id' => $admin->id,
        ]);
        $this->assertSame(1, \App\Models\AdminAuditLog::query()->where('auditable_type', 'platform_setting')->count());
    }

    public function test_unchanged_save_reports_nothing_changed(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);

        $this->putJson(route('admin.settings.update', 'otp'), $this->otpPayload())
            ->assertOk()
            ->assertJsonPath('message', __('admin.settings.unchanged'));

        $this->assertDatabaseCount('platform_settings', 0);
    }

    public function test_a_stale_form_is_rejected_with_409(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);
        $stale = app(SettingsService::class)->fingerprint(SettingGroup::Otp);
        app(SettingsService::class)->set(SettingKey::OtpMaxAttempts, 7);

        $this->putJson(route('admin.settings.update', 'otp'), $this->otpPayload(['otp_length' => '7']) + ['fingerprint' => $stale])
            ->assertStatus(409)
            ->assertJsonPath('errors._code', 'stale_version');

        $this->assertSame(6, app(SettingsService::class)->get(SettingKey::OtpLength));
    }

    public function test_business_rule_violations_show_inline_on_the_field(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);

        $this->putJson(route('admin.settings.update', 'otp'), $this->otpPayload(['otp_resend_cooldown_seconds' => '400']))
            ->assertStatus(422)
            ->assertJsonPath('errors._code', 'setting_otp_cooldown_exceeds_ttl')
            ->assertJsonStructure(['errors' => ['settings.otp_resend_cooldown_seconds']]);
    }

    public function test_list_and_boolean_settings_round_trip_through_the_form(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsView, AdminPermission::SettingsManage]);
        $settings = app(SettingsService::class);

        $this->putJson(route('admin.settings.update', 'dispatch'), ['settings' => [
            'dispatch_radius_steps_km' => '1, 2.5, 6',
            'dispatch_batch_size' => '15',
            'dispatch_wave_interval_seconds' => '45',
            'location_stale_after_seconds' => '120',
            'location_max_speed_kmh' => '180',
            'location_history_interval_seconds' => '20',
            'auto_redispatch_on_provider_cancel' => '0',
        ]])->assertOk();

        $this->assertSame([1.0, 2.5, 6.0], $settings->get(SettingKey::DispatchRadiusStepsKm));
        $this->assertFalse($settings->bool(SettingKey::AutoRedispatchOnProviderCancel));

        $this->putJson(route('admin.settings.update', 'security'), ['settings' => [
            'admin_mfa_required_roles' => ['finance_manager', 'super_admin'],
            'admin_login_lockout_seconds' => '600',
        ]])->assertOk();

        $this->assertSame(['finance_manager', 'super_admin'], $settings->list(SettingKey::AdminMfaRequiredRoles));
        $this->assertSame(600, $settings->int(SettingKey::AdminLoginLockoutSeconds));
    }

    public function test_unknown_groups_are_not_found(): void
    {
        $this->actingAsAdmin([AdminPermission::SettingsManage]);

        $this->putJson('/admin/settings/secrets', [])->assertNotFound();
    }

    /**
     * @param array<string, string> $override
     * @return array{settings: array<string, string>}
     */
    private function otpPayload(array $override = []): array
    {
        return ['settings' => $override + [
            'otp_length' => '6',
            'otp_ttl_seconds' => '300',
            'otp_resend_cooldown_seconds' => '60',
            'otp_max_attempts' => '5',
        ]];
    }
}
