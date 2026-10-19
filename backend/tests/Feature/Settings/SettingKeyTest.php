<?php

namespace Tests\Feature\Settings;

use App\Enums\Settings\SettingGroup;
use App\Enums\Settings\SettingKey;
use App\Enums\Settings\SettingType;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * The SettingKey enum is the single source of truth for type, default, rules and group (ADR-10).
 */
class SettingKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_key_belongs_to_exactly_one_group_and_every_group_has_keys(): void
    {
        $grouped = [];

        foreach (SettingGroup::cases() as $group) {
            $this->assertNotEmpty($group->keys(), "Group [{$group->value}] has no keys.");
            array_push($grouped, ...$group->keys());
        }

        $this->assertEqualsCanonicalizing(SettingKey::cases(), $grouped);
    }

    public function test_every_default_satisfies_its_own_rules(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        foreach (SettingKey::cases() as $key) {
            $rules = ['value' => $key->rules()];

            if ($key->itemRules() !== []) {
                $rules['value.*'] = $key->itemRules();
            }

            $validator = Validator::make(['value' => $key->default()], $rules);

            $this->assertFalse($validator->fails(), "Default of [{$key->value}] violates its rules: ".json_encode($validator->errors()->all()));
        }
    }

    public function test_every_default_has_the_declared_type(): void
    {
        foreach (SettingKey::cases() as $key) {
            $default = $key->default();

            match ($key->type()) {
                SettingType::Boolean => $this->assertIsBool($default, $key->value),
                SettingType::Integer => $this->assertIsInt($default, $key->value),
                SettingType::String => $this->assertTrue(is_string($default) || ($default === null && $key->isNullable()), $key->value),
                SettingType::StringList, SettingType::NumberList => $this->assertIsArray($default, $key->value),
            };
        }
    }

    public function test_integer_defaults_respect_their_bounds(): void
    {
        foreach (SettingKey::cases() as $key) {
            if ($key->bounds() !== null) {
                $this->assertGreaterThanOrEqual($key->bounds()[0], $key->default(), $key->value);
                $this->assertLessThanOrEqual($key->bounds()[1], $key->default(), $key->value);
            }
        }
    }

    public function test_every_key_is_labelled_in_arabic_and_english_and_never_sensitive(): void
    {
        foreach (SettingKey::cases() as $key) {
            $this->assertFalse($key->isSensitive());
            $this->assertDoesNotMatchRegularExpression('/secret|password|token|api_?key|private/i', $key->value, 'Secrets stay in .env, not in settings.');

            foreach (['ar', 'en'] as $locale) {
                app()->setLocale($locale);
                $this->assertNotSame($key->descriptionKey(), $key->description(), "[{$key->value}] description missing in {$locale}");
                $this->assertNotSame("admin.settings.keys.{$key->value}.label", $key->label(), "[{$key->value}] label missing in {$locale}");
            }
        }

        foreach (SettingGroup::cases() as $group) {
            foreach (['ar', 'en'] as $locale) {
                app()->setLocale($locale);
                $this->assertNotSame("enums.setting_group.{$group->value}", $group->label());
            }
        }
    }

    public function test_config_backed_keys_default_to_the_config_values(): void
    {
        config([
            'admin.auth.mfa.required_roles' => ['finance_manager', 'unknown_role'],
            'admin.auth.login.lockout_seconds' => 1234,
            'rate_limits.api.per_minute' => 77,
            'rate_limits.otp-request.per_hour' => 9,
            'rate_limits.admin-login.per_minute' => 4,
        ]);

        $this->assertSame(['finance_manager'], SettingKey::AdminMfaRequiredRoles->default());
        $this->assertSame(1234, SettingKey::AdminLoginLockoutSeconds->default());
        $this->assertSame(77, SettingKey::RateLimitApiPerMinute->default());
        $this->assertSame(9, SettingKey::RateLimitOtpRequestPerHour->default());
        $this->assertSame(4, SettingKey::RateLimitAdminLoginPerMinute->default());
    }

    public function test_input_is_normalized_into_typed_values(): void
    {
        $this->assertSame(6, SettingKey::OtpLength->fromInput(' 6 '));
        $this->assertSame('abc', SettingKey::OtpLength->fromInput('abc'));
        $this->assertTrue(SettingKey::RidePinRequired->fromInput('1'));
        $this->assertFalse(SettingKey::RidePinRequired->fromInput('0'));
        $this->assertFalse(SettingKey::RidePinRequired->fromInput(null));
        $this->assertNull(SettingKey::SupportPhone->fromInput('  '));
        $this->assertSame('', SettingKey::MinAppVersionCustomer->fromInput(null));
        $this->assertSame([2.0, 4.5, 7.0], SettingKey::DispatchRadiusStepsKm->fromInput('2, 4.5 ،7'));
        $this->assertSame(['ar', 'en'], SettingKey::SupportedLocales->fromInput(['ar', 'en', 'ar', '']));
        $this->assertSame([], SettingKey::AdminMfaRequiredRoles->fromInput(null));
    }

    public function test_invalid_stored_values_fall_back_to_the_default(): void
    {
        $this->assertSame(6, SettingKey::OtpLength->fromStored('six'));
        $this->assertSame([2.0, 4.0, 7.0], SettingKey::DispatchRadiusStepsKm->fromStored([]));
        $this->assertSame([5.0, 9.0], SettingKey::DispatchRadiusStepsKm->fromStored([5, '9', 'x']));
        $this->assertNull(SettingKey::SupportEmail->fromStored(null));
    }

    public function test_typed_rules_reject_out_of_range_and_malformed_values(): void
    {
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);

        $check = fn (SettingKey $key, mixed $value): bool => ! Validator::make(['v' => $value], ['v' => $key->rules()] + ($key->itemRules() === [] ? [] : ['v.*' => $key->itemRules()]))->fails();

        $this->assertFalse($check(SettingKey::OtpLength, 3));
        $this->assertTrue($check(SettingKey::OtpLength, 6));
        $this->assertFalse($check(SettingKey::MinAppVersionDriver, '1.2'));
        $this->assertTrue($check(SettingKey::MinAppVersionDriver, '1.2.3'));
        $this->assertFalse($check(SettingKey::SupportEmail, 'nope'));
        $this->assertFalse($check(SettingKey::SupportPhone, 'abc'));
        $this->assertTrue($check(SettingKey::SupportPhone, '+20 100 000 0000'));
        $this->assertFalse($check(SettingKey::SupportedLocales, ['ar', 'fr']));
        $this->assertFalse($check(SettingKey::DefaultCountry, 'ZZ'));
        $this->assertTrue($check(SettingKey::DefaultCountry, 'EG'));
        $this->assertFalse($check(SettingKey::DispatchRadiusStepsKm, [0, 3]));
        $this->assertFalse($check(SettingKey::AdminMfaRequiredRoles, ['ghost']));
    }
}
