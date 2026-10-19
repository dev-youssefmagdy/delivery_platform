<?php

namespace Tests\Feature\Settings;

use App\DTOs\Settings\SettingsData;
use App\Enums\Common\AuditAction;
use App\Enums\Settings\SettingGroup;
use App\Enums\Settings\SettingKey;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Services\Settings\SettingsService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Typed get/set, cache invalidation, audit before/after and cross-field rules (ADR-10).
 */
class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        $this->settings = app(SettingsService::class);
    }

    public function test_unset_keys_return_their_typed_defaults(): void
    {
        $this->assertSame(6, $this->settings->get(SettingKey::OtpLength));
        $this->assertSame('ar', $this->settings->string(SettingKey::DefaultLocale));
        $this->assertTrue($this->settings->bool(SettingKey::ProviderSingleActiveJob));
        $this->assertSame(['ar', 'en'], $this->settings->list(SettingKey::SupportedLocales));
        $this->assertNull($this->settings->get(SettingKey::SupportEmail));
    }

    public function test_set_persists_a_typed_value_and_audits_before_and_after(): void
    {
        $admin = Admin::factory()->create();

        $this->settings->set(SettingKey::OtpLength, '8', $admin, 'longer codes');

        $this->assertSame(8, $this->settings->get(SettingKey::OtpLength));
        $this->assertSame(8, DB::table('platform_settings')->where('key', 'otp_length')->value('value') * 1);

        $log = AdminAuditLog::query()->where('auditable_type', 'platform_setting')->sole();
        $this->assertSame(AuditAction::Updated, $log->action);
        $this->assertSame(['key' => 'otp_length', 'value' => 6], $log->before);
        $this->assertSame(['key' => 'otp_length', 'value' => 8], $log->after);
        $this->assertSame('longer codes', $log->reason);
        $this->assertSame('admin', $log->actor_type);
    }

    public function test_setting_the_same_value_writes_nothing(): void
    {
        $this->settings->set(SettingKey::OtpLength, 6);

        $this->assertDatabaseCount('platform_settings', 0);
        $this->assertSame(0, AdminAuditLog::query()->count());
    }

    public function test_invalid_values_are_rejected_and_not_stored(): void
    {
        try {
            $this->settings->set(SettingKey::OtpLength, 99);
            $this->fail('Expected a validation error.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('settings.otp_length', $e->errors());
        }

        $this->assertDatabaseCount('platform_settings', 0);
        $this->assertSame(6, $this->settings->get(SettingKey::OtpLength));
    }

    public function test_reads_are_cached_and_a_write_busts_the_cache(): void
    {
        $this->settings->get(SettingKey::OtpLength);

        DB::enableQueryLog();
        $this->settings->get(SettingKey::OtpTtlSeconds);
        $this->settings->get(SettingKey::DefaultLocale);
        $this->assertCount(0, DB::getQueryLog(), 'Cached reads must not query the database.');

        $this->settings->set(SettingKey::OtpTtlSeconds, 600);
        DB::flushQueryLog();
        $this->assertSame(600, $this->settings->get(SettingKey::OtpTtlSeconds));
        $this->assertCount(1, DB::getQueryLog(), 'The first read after a write reloads once.');
        $this->settings->get(SettingKey::OtpLength);
        $this->assertCount(1, DB::getQueryLog());
    }

    public function test_a_write_also_busts_the_public_app_config_cache(): void
    {
        Cache::put(\App\Support\Config\AppConfigCache::KEY, ['stale' => true]);

        $this->settings->set(SettingKey::SupportEmail, 'help@example.com');

        $this->assertFalse(Cache::has(\App\Support\Config\AppConfigCache::KEY));
    }

    public function test_update_group_saves_only_changed_keys_and_detects_stale_forms(): void
    {
        $admin = Admin::factory()->create();
        $group = SettingGroup::Otp;
        $fingerprint = $this->settings->fingerprint($group);

        $values = $this->settings->group($group);
        $values['otp_length'] = 7;

        $changed = $this->settings->updateGroup(new SettingsData($group, $values, $fingerprint), $admin);

        $this->assertSame([SettingKey::OtpLength], $changed);
        $this->assertSame(1, AdminAuditLog::query()->where('auditable_type', 'platform_setting')->count());

        // The same (now outdated) fingerprint is rejected.
        $this->expectException(ConflictException::class);
        $this->settings->updateGroup(new SettingsData($group, $values, $fingerprint), $admin);
    }

    public function test_an_unchanged_group_save_is_a_noop(): void
    {
        $admin = Admin::factory()->create();

        $changed = $this->settings->updateGroup(new SettingsData(SettingGroup::Otp, $this->settings->group(SettingGroup::Otp)), $admin);

        $this->assertSame([], $changed);
        $this->assertDatabaseCount('platform_settings', 0);
    }

    public function test_cross_field_rules_are_enforced_in_the_service(): void
    {
        $this->assertViolation('setting_default_locale_unsupported', fn () => $this->settings->set(SettingKey::SupportedLocales, ['en']));
        $this->assertViolation('setting_otp_cooldown_exceeds_ttl', fn () => $this->settings->set(SettingKey::OtpResendCooldownSeconds, 400));
        $this->assertViolation('setting_offer_ttl_exceeds_search_ttl', fn () => $this->settings->set(SettingKey::OfferTtlSeconds, 600));
        $this->assertViolation('setting_radius_steps_not_ascending', fn () => $this->settings->set(SettingKey::DispatchRadiusStepsKm, '5, 3'));

        $this->settings->set(SettingKey::DispatchRadiusStepsKm, '1.5, 3, 6');
        $this->assertSame([1.5, 3.0, 6.0], $this->settings->get(SettingKey::DispatchRadiusStepsKm));
        $this->assertDatabaseCount('platform_settings', 1);
    }

    public function test_group_fields_come_from_the_enum_metadata(): void
    {
        $fields = $this->settings->fieldsFor(SettingGroup::Localization);

        $this->assertCount(count(SettingGroup::Localization->keys()), $fields);
        $country = collect($fields)->firstWhere('key', SettingKey::DefaultCountry);
        $this->assertArrayHasKey('EG', $country->options);
        $multi = collect($fields)->firstWhere('key', SettingKey::SupportedLocales);
        $this->assertSame('settings[supported_locales][]', $multi->name);
    }

    /**
     * @param callable(): mixed $callback
     */
    private function assertViolation(string $code, callable $callback): void
    {
        try {
            $callback();
            $this->fail("Expected [{$code}].");
        } catch (BusinessRuleViolation $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }
}
