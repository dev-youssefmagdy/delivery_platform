<?php

namespace App\Providers;

use App\Enums\Common\RateLimiterName;
use App\Enums\Settings\SettingKey;
use App\Models\AccountDeletionRequest;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\AdminExport;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CustomerProfile;
use App\Models\DemoItem;
use App\Models\IdempotencyKey;
use App\Models\OtpChallenge;
use App\Models\PlatformSetting;
use App\Models\PolicyVersion;
use App\Models\ProviderProfile;
use App\Models\Role;
use App\Models\SavedPlace;
use App\Models\ServiceCategory;
use App\Models\ServiceCategoryVersion;
use App\Models\ServiceFamily;
use App\Models\ServiceFormSchema;
use App\Models\ServiceZone;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\UserDevice;
use App\Models\VehicleCategory;
use App\Models\ZoneServiceCategory;
use App\Services\Settings\SettingsService;
use App\Support\Auth\PhoneNumber;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\DatabaseCurrencyRegistry;
use App\Support\Money\MoneyFormatter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrencyRegistry::class, DatabaseCurrencyRegistry::class);
        $this->app->singleton(MoneyFormatter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        /*
         * Stable aliases are stored in polymorphic columns (audit logs, tokens, permissions) instead of class
         * names. Every model used polymorphically must be added here.
         */
        Relation::enforceMorphMap([
            'user' => User::class,
            'admin' => Admin::class,
            'role' => Role::class,
            'currency' => Currency::class,
            'country' => Country::class,
            'admin_audit_log' => AdminAuditLog::class,
            'idempotency_key' => IdempotencyKey::class,
            'admin_export' => AdminExport::class,
            'demo_item' => DemoItem::class,
            'platform_setting' => PlatformSetting::class,
            'policy_version' => PolicyVersion::class,
            'customer_profile' => CustomerProfile::class,
            'provider_profile' => ProviderProfile::class,
            'otp_challenge' => OtpChallenge::class,
            'user_device' => UserDevice::class,
            'user_consent' => UserConsent::class,
            'account_deletion_request' => AccountDeletionRequest::class,
            'service_family' => ServiceFamily::class,
            'service_category' => ServiceCategory::class,
            'service_category_version' => ServiceCategoryVersion::class,
            'service_form_schema' => ServiceFormSchema::class,
            'vehicle_category' => VehicleCategory::class,
            'city' => City::class,
            'service_zone' => ServiceZone::class,
            'zone_service_category' => ZoneServiceCategory::class,
            'saved_place' => SavedPlace::class,
            // @admin-module:morph-map
        ]);

        // Super Admin passes every ability check (Plan Appendix B); every other admin is checked by Spatie.
        Gate::before(static fn (Authorizable $user): ?bool => $user instanceof Admin && $user->isSuperAdmin() ? true : null);

        $this->configureRateLimiting();
    }

    /**
     * The phone as the rate limiters see it: normalised, so formatting tricks do not create new buckets.
     */
    private static function limiterPhone(Request $request): string
    {
        $phone = (string) $request->input('phone');

        return PhoneNumber::normalize($phone) ?? $phone;
    }

    /**
     * Named limiters (Plan §4.4). Limits are typed settings (SettingKey::RateLimit*), defaulting to config/rate_limits.php.
     */
    private function configureRateLimiting(): void
    {
        // Limits are typed platform settings (defaults come from config/rate_limits.php); read per request, cached.
        $settings = static fn (SettingKey $key): int => app(SettingsService::class)->int($key);
        $perMinute = static fn (RateLimiterName $name): int => $settings(match ($name) {
            RateLimiterName::Api => SettingKey::RateLimitApiPerMinute,
            RateLimiterName::OtpRequest => SettingKey::RateLimitOtpRequestPerMinute,
            RateLimiterName::OtpVerify => SettingKey::RateLimitOtpVerifyPerMinute,
            RateLimiterName::Location => SettingKey::RateLimitLocationPerMinute,
            RateLimiterName::Offers => SettingKey::RateLimitOffersPerMinute,
            RateLimiterName::PublicForms => SettingKey::RateLimitPublicFormsPerMinute,
            RateLimiterName::AdminLogin => SettingKey::RateLimitAdminLoginPerMinute,
        });
        $actor = static fn (Request $request): string => (string) ($request->user()?->getAuthIdentifier() ?? 'ip:'.$request->ip());

        RateLimiter::for(RateLimiterName::Api->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::Api))->by($actor($request)));

        RateLimiter::for(RateLimiterName::OtpRequest->value, static function (Request $request) use ($perMinute, $settings): array {
            $phone = 'phone:'.hash('sha256', self::limiterPhone($request));
            $ip = 'ip:'.$request->ip();
            $perHour = $settings(SettingKey::RateLimitOtpRequestPerHour);
            $limits = [
                Limit::perMinute($perMinute(RateLimiterName::OtpRequest))->by($phone),
                Limit::perMinute($perMinute(RateLimiterName::OtpRequest))->by($ip),
                Limit::perHour($perHour)->by("hour:{$phone}"),
                Limit::perHour($perHour)->by("hour:{$ip}"),
            ];

            // Device signal (SRS §20.7): the same limits per install when the app reports its device id.
            $deviceId = $request->input('device_id');

            if (is_string($deviceId) && $deviceId !== '') {
                $device = 'device:'.hash('sha256', $deviceId);
                $limits[] = Limit::perMinute($perMinute(RateLimiterName::OtpRequest))->by($device);
                $limits[] = Limit::perHour($perHour)->by("hour:{$device}");
            }

            return $limits;
        });

        RateLimiter::for(RateLimiterName::OtpVerify->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::OtpVerify))
            ->by(hash('sha256', self::limiterPhone($request)).'|'.$request->ip()));

        RateLimiter::for(RateLimiterName::Location->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::Location))->by($actor($request)));

        RateLimiter::for(RateLimiterName::Offers->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::Offers))->by($actor($request)));

        RateLimiter::for(RateLimiterName::PublicForms->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::PublicForms))->by('ip:'.$request->ip()));

        // Validation-only (Precognition) calls are not login attempts, so they are not counted.
        RateLimiter::for(RateLimiterName::AdminLogin->value, static fn (Request $request): Limit => $request->isPrecognitive()
            ? Limit::none()
            : Limit::perMinute($perMinute(RateLimiterName::AdminLogin))->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
