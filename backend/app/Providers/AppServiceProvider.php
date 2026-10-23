<?php

namespace App\Providers;

use App\Enums\Common\RateLimiterName;
use App\Enums\Settings\SettingKey;
use App\Models\AccountDeletionRequest;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\AdminExport;
use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\BookingStop;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CustomerMessage;
use App\Models\CustomerProfile;
use App\Models\DemoItem;
use App\Models\DispatchRecipient;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\CancellationPolicy;
use App\Models\CommissionRule;
use App\Models\CommissionRuleVersion;
use App\Models\FarePolicy;
use App\Models\IdempotencyKey;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\LedgerReconciliationRun;
use App\Models\GatewayWebhookEvent;
use App\Models\OtpChallenge;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentReconciliationRun;
use App\Models\PaymentRefund;
use App\Models\OwnedDocument;
use App\Models\PlatformSetting;
use App\Models\PolicyVersion;
use App\Models\ProviderDocument;
use App\Models\ProviderLastLocation;
use App\Models\ProviderLocation;
use App\Models\ProviderProfile;
use App\Models\ProviderServiceCategory;
use App\Models\ProviderStatusEvent;
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
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use App\Models\ZoneServiceCategory;
use App\Enums\Reporting\ReportKey;
use App\Policies\DocumentPolicy;
use App\Policies\ReportPolicy;
use App\Services\Booking\DispatchableBookingSource;
use App\Services\Dispatch\BookingEventSequencer;
use App\Services\Dispatch\DispatchableRequestSource;
use App\Services\Dispatch\EventSequencer;
use App\Services\Location\Index\DatabaseGeoIndex;
use App\Services\Location\Index\ProviderGeoIndex;
use App\Services\Location\Index\RedisGeoIndex;
use App\Services\Settings\SettingsService;
use App\Support\Auth\PhoneNumber;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\DatabaseCurrencyRegistry;
use App\Support\Money\MoneyFormatter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Foundation\Application;
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

        // Realtime / dispatch seams (P2.2), wired to bookings in P2.3 (ADR-89): dispatch reads the booking through
        // DispatchableBookingSource and every booking stream shares the `bookings.sequence` counter.
        $this->app->bind(DispatchableRequestSource::class, DispatchableBookingSource::class);
        $this->app->bind(EventSequencer::class, BookingEventSequencer::class);
        $this->app->bind(ProviderGeoIndex::class, static fn (Application $app): ProviderGeoIndex => config('location.index') === 'redis'
            ? $app->make(RedisGeoIndex::class)
            : $app->make(DatabaseGeoIndex::class));

        // Risk detectors (P5.2): one tag, the evaluation service runs them all. A new detector is added to this list only.
        $this->app->tag([
            \App\Services\Risk\Detectors\CancellationRateDetector::class,
            \App\Services\Risk\Detectors\NoShowDetector::class,
            \App\Services\Risk\Detectors\OfferSpamDetector::class,
            \App\Services\Risk\Detectors\LocationAnomalyDetector::class,
            \App\Services\Risk\Detectors\ReferralAbuseDetector::class,
            \App\Services\Risk\Detectors\AccountFarmDetector::class,
            \App\Services\Risk\Detectors\PaymentDisputeDetector::class,
        ], 'risk.detectors');
        $this->app->when(\App\Services\Risk\RiskSignalEvaluationService::class)->needs('$detectors')->giveTagged('risk.detectors');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // The public website's URL map (P5.5), available to every Blade view as $sitePaths.
        \Illuminate\Support\Facades\View::composer('components.website.layouts.site', \App\View\Composers\SiteChromeComposer::class);
        \Illuminate\Support\Facades\View::share('sitePaths', $this->app->make(\App\Support\Website\SitePaths::class));

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
            'vehicle' => Vehicle::class,
            'vehicle_photo' => VehiclePhoto::class,
            'document_type' => DocumentType::class,
            'document_requirement' => DocumentRequirement::class,
            'provider_document' => ProviderDocument::class,
            'vehicle_document' => VehicleDocument::class,
            'provider_service_category' => ProviderServiceCategory::class,
            'provider_status_event' => ProviderStatusEvent::class,
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
            'customer_message' => CustomerMessage::class,
            'provider_location' => ProviderLocation::class,
            'provider_last_location' => ProviderLastLocation::class,
            'dispatch_recipient' => DispatchRecipient::class,
            'booking' => Booking::class,
            'booking_stop' => BookingStop::class,
            'booking_status_event' => BookingStatusEvent::class,
            'fare_policy' => FarePolicy::class,
            'cancellation_policy' => CancellationPolicy::class,
            'ledger_account' => LedgerAccount::class,
            'journal_entry' => JournalEntry::class,
            'journal_line' => JournalLine::class,
            'ledger_reconciliation_run' => LedgerReconciliationRun::class,
            'commission_rule' => CommissionRule::class,
            'commission_rule_version' => CommissionRuleVersion::class,
            'payment' => Payment::class,
            'payment_method' => PaymentMethod::class,
            'payment_refund' => PaymentRefund::class,
            'gateway_webhook_event' => GatewayWebhookEvent::class,
            'payment_reconciliation_run' => PaymentReconciliationRun::class,
            'coupon' => \App\Models\Coupon::class,
            'coupon_reservation' => \App\Models\CouponReservation::class,
            'provider_payout_method' => \App\Models\ProviderPayoutMethod::class,
            'payout_request' => \App\Models\PayoutRequest::class,
            'payout_batch' => \App\Models\PayoutBatch::class,
            'financial_adjustment_request' => \App\Models\FinancialAdjustmentRequest::class,
            'booking_adjustment_request' => \App\Models\BookingAdjustmentRequest::class,
            'booking_receipt' => \App\Models\BookingReceipt::class,
            'cash_reconciliation' => \App\Models\CashReconciliation::class,
            'notification_template' => \App\Models\NotificationTemplate::class,
            'push_campaign' => \App\Models\PushCampaign::class,
            'app_notification' => \App\Models\AppNotification::class,
            'notification_delivery' => \App\Models\NotificationDelivery::class,
            'support_ticket' => \App\Models\SupportTicket::class,
            'support_macro' => \App\Models\SupportMacro::class,
            'safety_incident' => \App\Models\SafetyIncident::class,
            'trusted_contact' => \App\Models\TrustedContact::class,
            'booking_rating' => \App\Models\BookingRating::class,
            'rating_report' => \App\Models\RatingReport::class,
            'rating_appeal' => \App\Models\RatingAppeal::class,
            'risk_signal' => \App\Models\RiskSignal::class,
            'lead' => \App\Models\Lead::class,
            'content_page' => \App\Models\ContentPage::class,
            'content_article' => \App\Models\ContentArticle::class,
            'content_faq' => \App\Models\ContentFaq::class,
            'content_banner' => \App\Models\ContentBanner::class,
            'content_menu' => \App\Models\ContentMenu::class,
            'content_category' => \App\Models\ContentCategory::class,
            'content_app_link' => \App\Models\ContentAppLink::class,
            'content_media' => \App\Models\ContentMedia::class,
            // @admin-module:morph-map
        ]);

        // The shared base class of provider/vehicle documents: one policy covers both subclasses.
        Gate::policy(OwnedDocument::class, DocumentPolicy::class);
        Gate::policy(ReportKey::class, ReportPolicy::class);

        foreach ([\App\Models\ContentPage::class, \App\Models\ContentArticle::class, \App\Models\ContentFaq::class, \App\Models\ContentBanner::class, \App\Models\ContentMenu::class] as $managed) {
            Gate::policy($managed, \App\Policies\ManagedContentPolicy::class);
        }

        foreach ([\App\Models\ContentCategory::class, \App\Models\ContentAppLink::class, \App\Models\ContentMedia::class] as $asset) {
            Gate::policy($asset, \App\Policies\ContentAssetPolicy::class);
        }

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
            RateLimiterName::ProviderUploads => SettingKey::RateLimitProviderUploadsPerMinute,
            RateLimiterName::PaymentWebhooks => SettingKey::RateLimitPaymentWebhooksPerMinute,
        });
        $actor = static fn (Request $request): string => (string) ($request->user()?->getAuthIdentifier() ?? 'ip:'.$request->ip());

        RateLimiter::for(RateLimiterName::Api->value, static fn (Request $request): Limit => $request->routeIs('api.v1.safety.incidents.store') && $request->input('trigger') === 'sos'
            // An SOS is never refused by a rate limit (SRS §13.2); repeated presses join the open case in the service.
            ? Limit::none()
            : Limit::perMinute($perMinute(RateLimiterName::Api))->by($actor($request)));

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

        RateLimiter::for(RateLimiterName::ProviderUploads->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::ProviderUploads))->by($actor($request)));

        // Gateway callbacks have no user: bucket by IP (the signature is what authenticates them).
        RateLimiter::for(RateLimiterName::PaymentWebhooks->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::PaymentWebhooks))->by('ip:'.$request->ip()));

        RateLimiter::for(RateLimiterName::PublicForms->value, static fn (Request $request): Limit => Limit::perMinute($perMinute(RateLimiterName::PublicForms))->by('ip:'.$request->ip()));

        // Validate-before-submit calls of the public website forms (P5.5): separate and more generous than the real submit.
        RateLimiter::for('website-precognition', static fn (Request $request): Limit => Limit::perMinute((int) config('website.forms.precognition_per_minute', 40))->by('ip:'.$request->ip()));

        // Validation-only (Precognition) calls are not login attempts, so they are not counted.
        RateLimiter::for(RateLimiterName::AdminLogin->value, static fn (Request $request): Limit => $request->isPrecognitive()
            ? Limit::none()
            : Limit::perMinute($perMinute(RateLimiterName::AdminLogin))->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
