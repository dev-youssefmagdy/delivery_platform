<?php

namespace App\Providers;

use App\Enums\Common\RateLimiterName;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\AdminExport;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DemoItem;
use App\Models\IdempotencyKey;
use App\Models\Role;
use App\Models\User;
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
            // @admin-module:morph-map
        ]);

        // Super Admin passes every ability check (Plan Appendix B); every other admin is checked by Spatie.
        Gate::before(static fn (Authorizable $user): ?bool => $user instanceof Admin && $user->isSuperAdmin() ? true : null);

        $this->configureRateLimiting();
    }

    /**
     * Named limiters (Plan §4.4). Limits come from config/rate_limits.php.
     */
    private function configureRateLimiting(): void
    {
        $perMinute = static fn (RateLimiterName $name): int => (int) config("rate_limits.{$name->value}.per_minute", 60);
        $actor = static fn (Request $request): string => (string) ($request->user()?->getAuthIdentifier() ?? 'ip:'.$request->ip());

        RateLimiter::for(RateLimiterName::Api->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::Api))->by($actor($request)));

        RateLimiter::for(RateLimiterName::OtpRequest->value, static function (Request $request) use ($perMinute): array {
            $phone = 'phone:'.hash('sha256', (string) $request->input('phone'));
            $ip = 'ip:'.$request->ip();
            $perHour = (int) config('rate_limits.otp-request.per_hour', 10);

            return [
                Limit::perMinute($perMinute(RateLimiterName::OtpRequest))->by($phone),
                Limit::perMinute($perMinute(RateLimiterName::OtpRequest))->by($ip),
                Limit::perHour($perHour)->by("hour:{$phone}"),
                Limit::perHour($perHour)->by("hour:{$ip}"),
            ];
        });

        RateLimiter::for(RateLimiterName::OtpVerify->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::OtpVerify))
            ->by(hash('sha256', (string) $request->input('phone')).'|'.$request->ip()));

        RateLimiter::for(RateLimiterName::Location->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::Location))->by($actor($request)));

        RateLimiter::for(RateLimiterName::Offers->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::Offers))->by($actor($request)));

        RateLimiter::for(RateLimiterName::PublicForms->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::PublicForms))->by('ip:'.$request->ip()));

        // Validation-only (Precognition) calls are not login attempts, so they are not counted.
        RateLimiter::for(RateLimiterName::AdminLogin->value, static fn (Request $request): Limit => $request->isPrecognitive()
            ? Limit::none()
            : Limit::perMinute($perMinute(RateLimiterName::AdminLogin))->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
