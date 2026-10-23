<?php

namespace Tests\Feature\Api;

use App\Enums\Settings\SettingKey;
use App\Models\Admin;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PolicyVersion;
use App\Services\Policy\PolicyVersionService;
use App\Services\Settings\SettingsService;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * GET /api/v1/config/app and /api/v1/locales (Plan P1.2 step 6).
 */
class AppConfigEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    public function test_response_shape_is_stable(): void
    {
        $response = $this->getJson('/api/v1/config/app')->assertOk();

        $response->assertExactJsonStructure([
            'success', 'message',
            'data' => [
                'default_locale', 'locales',
                'country' => ['iso2', 'phone_code', 'timezone'],
                'currency' => ['code', 'exponent', 'symbol' => ['ar', 'en']],
                'digits_preference',
                'support' => ['phone', 'email', 'whatsapp'],
                'maintenance',
                'apps' => ['customer' => ['min_version', 'force_update'], 'provider' => ['min_version', 'force_update']],
                'policies', 'features',
                'safety' => ['emergency_numbers', 'sos_hold_seconds', 'trusted_contacts_max', 'emergency_dispatch'],
                'flags',
            ],
            'meta', 'errors',
        ]);

        $this->assertSame([
            'default_locale' => 'ar',
            'locales' => ['ar', 'en'],
            'country' => ['iso2' => 'EG', 'phone_code' => '+20', 'timezone' => 'Africa/Cairo'],
            'currency' => ['code' => 'EGP', 'exponent' => 2, 'symbol' => ['ar' => 'ج.م', 'en' => 'EGP']],
            'digits_preference' => 'auto',
            'support' => ['phone' => null, 'email' => null, 'whatsapp' => null],
            'maintenance' => false,
            'apps' => [
                'customer' => ['min_version' => '1.0.0', 'force_update' => false],
                'provider' => ['min_version' => '1.0.0', 'force_update' => false],
            ],
            'policies' => [],
            'features' => [],
            'flags' => [],
        ], Arr::except($response->json('data'), ['safety']));

        // The platform shows local emergency numbers; it never dispatches an emergency call itself (SRS 13.2).
        $this->assertFalse($response->json('data.safety.emergency_dispatch'));
        $this->assertSame((int) config('safety.sos.hold_seconds'), $response->json('data.safety.sos_hold_seconds'));

        // Empty maps are JSON objects, never lists.
        $this->assertStringContainsString('"policies":{}', (string) $response->getContent());
        $this->assertStringContainsString('"features":{}', (string) $response->getContent());
        $this->assertStringContainsString('"flags":{}', (string) $response->getContent());
    }

    public function test_it_is_public_and_exposes_no_secrets(): void
    {
        $body = (string) $this->getJson('/api/v1/config/app')->getContent();

        $this->assertDoesNotMatchRegularExpression('/secret|password|token|api_key|private/i', $body);
    }

    public function test_configured_settings_are_reflected(): void
    {
        $settings = app(SettingsService::class);
        $settings->set(SettingKey::SupportPhone, '+20 100 000 0000');
        $settings->set(SettingKey::SupportEmail, 'help@example.com');
        $settings->set(SettingKey::DigitsPreference, 'arabic_indic');
        $settings->set(SettingKey::MaintenanceModeApps, true);
        $settings->set(SettingKey::MinAppVersionCustomer, '2.3.4');
        $settings->set(SettingKey::ForceUpdateCustomer, true);
        $settings->set(SettingKey::SupportedLocales, ['en', 'ar']);
        $settings->set(SettingKey::DefaultLocale, 'en');

        $data = $this->getJson('/api/v1/config/app')->assertOk()->json('data');

        $this->assertSame('+20 100 000 0000', $data['support']['phone']);
        $this->assertSame('help@example.com', $data['support']['email']);
        $this->assertSame('arabic_indic', $data['digits_preference']);
        $this->assertTrue($data['maintenance']);
        $this->assertSame(['min_version' => '2.3.4', 'force_update' => true], $data['apps']['customer']);
        $this->assertSame(['min_version' => '1.0.0', 'force_update' => false], $data['apps']['provider']);
        $this->assertSame(['en', 'ar'], $data['locales']);
        $this->assertSame('en', $data['default_locale']);
    }

    public function test_default_country_and_currency_follow_the_settings(): void
    {
        $kwd = Currency::factory()->create(['code' => 'KWD', 'exponent' => 3, 'symbol_ar' => 'د.ك', 'symbol_en' => 'KWD']);
        Country::factory()->create(['iso2' => 'KW', 'currency_code' => $kwd->code, 'timezone' => 'Asia/Kuwait', 'phone_code' => '+965']);
        app(SettingsService::class)->set(SettingKey::DefaultCountry, 'KW');
        app(SettingsService::class)->set(SettingKey::DefaultCurrency, 'KWD');

        $data = $this->getJson('/api/v1/config/app')->json('data');

        $this->assertSame(['iso2' => 'KW', 'phone_code' => '+965', 'timezone' => 'Asia/Kuwait'], $data['country']);
        $this->assertSame(3, $data['currency']['exponent']);
        $this->assertSame('KWD', $data['currency']['code']);
    }

    public function test_only_the_current_published_policy_version_per_type_is_listed(): void
    {
        PolicyVersion::factory()->create();
        PolicyVersion::factory()->archived(1)->create();
        $current = PolicyVersion::factory()->published(2)->create();
        PolicyVersion::factory()->published(1)->create(['type' => 'privacy']);

        $policies = $this->getJson('/api/v1/config/app')->json('data.policies');

        $this->assertSame(['privacy', 'terms'], array_keys($policies));
        $this->assertSame(['id' => $current->uuid, 'version' => 2, 'published_at' => $current->published_at->utc()->format('Y-m-d\TH:i:s\Z')], $policies['terms']);
    }

    public function test_changes_are_visible_immediately_despite_the_cache(): void
    {
        $this->getJson('/api/v1/config/app')->assertJsonPath('data.maintenance', false);

        app(SettingsService::class)->set(SettingKey::MaintenanceModeApps, true);
        $this->getJson('/api/v1/config/app')->assertJsonPath('data.maintenance', true);

        $policy = PolicyVersion::factory()->create();
        app(PolicyVersionService::class)->publish($policy, 'initial release', Admin::factory()->create());
        $this->getJson('/api/v1/config/app')->assertJsonPath('data.policies.terms.id', $policy->uuid)->assertJsonPath('data.policies.terms.version', 1);
    }

    public function test_etag_enables_conditional_requests(): void
    {
        $first = $this->getJson('/api/v1/config/app')->assertOk();
        $etag = $first->headers->get('ETag');

        $this->assertNotEmpty($etag);
        $this->assertStringContainsString('max-age=30', (string) $first->headers->get('Cache-Control'));
        $this->assertStringContainsString('Accept-Language', (string) $first->headers->get('Vary'));

        $this->getJson('/api/v1/config/app', ['If-None-Match' => $etag])->assertStatus(304);

        app(SettingsService::class)->set(SettingKey::SupportEmail, 'new@example.com');
        $changed = $this->getJson('/api/v1/config/app', ['If-None-Match' => $etag])->assertOk();
        $this->assertNotSame($etag, $changed->headers->get('ETag'));
    }

    public function test_locales_endpoint_lists_supported_locales_with_native_names(): void
    {
        $this->getJson('/api/v1/locales')
            ->assertOk()
            ->assertJsonPath('data', [
                ['code' => 'ar', 'name' => 'العربية', 'direction' => 'rtl', 'is_default' => true],
                ['code' => 'en', 'name' => 'English', 'direction' => 'ltr', 'is_default' => false],
            ]);

        app(SettingsService::class)->set(SettingKey::SupportedLocales, ['ar']);
        $this->getJson('/api/v1/locales')->assertJsonCount(1, 'data');
    }

    public function test_the_response_language_follows_accept_language_but_data_does_not(): void
    {
        $ar = $this->getJson('/api/v1/config/app', ['Accept-Language' => 'ar'])->json();
        $en = $this->getJson('/api/v1/config/app', ['Accept-Language' => 'en'])->json();

        $this->assertNotSame($ar['message'], $en['message']);
        $this->assertSame($ar['data'], $en['data']);
    }
}
