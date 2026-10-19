<?php

namespace Tests\Feature\Settings;

use App\DTOs\Admin\LoginData;
use App\Enums\Admin\AdminRole;
use App\Enums\Common\RateLimiterName;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Models\Admin;
use App\Services\Admin\AdminAuthService;
use App\Services\Settings\SettingsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\InteractsWithAdmin;
use Tests\Support\AdminTotp;
use Tests\TestCase;

/**
 * P1.1 carry-over: admin MFA roles, the admin sign-in lock-out and the rate limits are typed settings now, with
 * the old config values as defaults.
 */
class ConfigMovedToSettingsTest extends TestCase
{
    use InteractsWithAdmin;
    use RefreshDatabase;

    public function test_mfa_required_roles_come_from_the_setting_and_default_to_config(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = $this->actingAsAdmin();
        $admin->assignRole(AdminRole::FinanceManager->value);

        // Config default: nobody is forced (phpunit empties ADMIN_MFA_REQUIRED_ROLES).
        $this->get(route('admin.home'))->assertOk();

        // The stored setting wins over config.
        app(SettingsService::class)->set(SettingKey::AdminMfaRequiredRoles, [AdminRole::FinanceManager->value]);
        $this->get(route('admin.home'))->assertRedirect(route('admin.security.mfa'));

        AdminTotp::enable($admin);
        $this->get(route('admin.home'))->assertOk();

        // Removing the role from the setting lifts the requirement again.
        app(SettingsService::class)->set(SettingKey::AdminMfaRequiredRoles, []);
        config(['admin.auth.mfa.required_roles' => [AdminRole::FinanceManager->value]]);
        $this->assertSame([], app(SettingsService::class)->list(SettingKey::AdminMfaRequiredRoles));
    }

    public function test_rate_limits_are_read_from_settings(): void
    {
        Route::middleware(['api', RateLimiterName::OtpRequest->middleware()])->post('api/v1/_test/otp-setting', fn () => response()->noContent());
        app(SettingsService::class)->set(SettingKey::RateLimitOtpRequestPerMinute, 1);

        $this->postJson('/api/v1/_test/otp-setting', ['phone' => '+201000000009'])->assertNoContent();
        $this->postJson('/api/v1/_test/otp-setting', ['phone' => '+201000000009'])->assertStatus(429);
    }

    public function test_rate_limit_defaults_follow_config_until_overridden(): void
    {
        config(['rate_limits.public-forms.per_minute' => 2]);
        Route::middleware(['api', RateLimiterName::PublicForms->middleware()])->post('api/v1/_test/forms', fn () => response()->noContent());

        $this->postJson('/api/v1/_test/forms')->assertNoContent();
        $this->postJson('/api/v1/_test/forms')->assertNoContent();
        $this->postJson('/api/v1/_test/forms')->assertStatus(429);

        RateLimiter::clear(md5('public-forms'.'ip:127.0.0.1'));
        Cache::flush();
        app(SettingsService::class)->set(SettingKey::RateLimitPublicFormsPerMinute, 50);
        $this->postJson('/api/v1/_test/forms')->assertNoContent();
    }

    public function test_the_admin_login_lockout_and_attempt_limit_are_settings(): void
    {
        $admin = Admin::factory()->create();
        $settings = app(SettingsService::class);
        $settings->set(SettingKey::RateLimitAdminLoginPerMinute, 2);
        $settings->set(SettingKey::AdminLoginLockoutSeconds, 120);
        RateLimiter::clear('admin-login:'.mb_strtolower($admin->email).'|127.0.0.1');

        $service = app(AdminAuthService::class);
        $attempt = static function () use ($service, $admin): void {
            $service->attempt(new LoginData($admin->email, 'wrong-Password-1!', false));
        };

        foreach ([1, 2] as $ignored) {
            try {
                $attempt();
                $this->fail('Wrong password must be rejected.');
            } catch (BusinessRuleViolation $e) {
                $this->assertSame('invalid_credentials', $e->errorCode);
            }
        }

        try {
            $attempt();
            $this->fail('The third attempt must be locked out.');
        } catch (ThrottleRequestsException $e) {
            $retryAfter = (int) ($e->getHeaders()['Retry-After'] ?? 0);
            $this->assertGreaterThan(60, $retryAfter, 'Lock-out uses the configured duration, not the 60 s default.');
            $this->assertLessThanOrEqual(120, $retryAfter);
        }
    }
}
