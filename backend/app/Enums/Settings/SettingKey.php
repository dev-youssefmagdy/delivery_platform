<?php

namespace App\Enums\Settings;

use App\Enums\Admin\AdminRole;
use App\Enums\Common\Locale;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;
use App\Rules\AdminIpAllowlistRule;
use Illuminate\Validation\Rule;

/**
 * Every platform setting (ADR-10, Plan P1.2). One case per key: type, default, validation rules, group and
 * admin input are declared here, so the settings screen, validation, the typed reader and the app-config API
 * cannot drift apart. Unknown keys do not exist. Secrets never live here (they stay in .env): `isSensitive()`
 * is false for every key and a test fails if a key name looks like a secret.
 *
 * Defaults that existed in config files before P1.2 (admin MFA roles, login lock-out, rate limits) read those
 * config values, so deployments keep their env-driven behaviour until an admin overrides it in the panel.
 */
enum SettingKey: string implements HasLabel
{
    use EnumHelpers;

    // Localization & market
    case DefaultCountry = 'default_country';
    case DefaultLocale = 'default_locale';
    case SupportedLocales = 'supported_locales';
    case DefaultCurrency = 'default_currency';
    case DigitsPreference = 'digits_preference';

    // Support contacts
    case SupportPhone = 'support_phone';
    case SupportEmail = 'support_email';
    case SupportWhatsapp = 'support_whatsapp';

    // OTP
    case OtpLength = 'otp_length';
    case OtpTtlSeconds = 'otp_ttl_seconds';
    case OtpResendCooldownSeconds = 'otp_resend_cooldown_seconds';
    case OtpMaxAttempts = 'otp_max_attempts';

    // Booking & offers
    case BookingSearchTtlSeconds = 'booking_search_ttl_seconds';
    case OfferTtlSeconds = 'offer_ttl_seconds';
    case MaxOffersPerProviderPerBooking = 'max_offers_per_provider_per_booking';
    case MaxOfferRevisions = 'max_offer_revisions';
    case MaxActiveOnDemandBookings = 'max_active_on_demand_bookings';
    case ScheduledBookingMinLeadMinutes = 'scheduled_booking_min_lead_minutes';
    case ScheduledBookingMaxDays = 'scheduled_booking_max_days';
    case ScheduledSearchLeadMinutes = 'scheduled_search_lead_minutes';
    case RatingCustomerByProviderEnabled = 'rating_customer_by_provider_enabled';
    case RatingWindowHours = 'rating_window_hours';
    case RatingMinSample = 'rating_min_sample';
    case CancellationAbuseThreshold = 'cancellation_abuse_threshold';
    case CancellationAbuseWindowHours = 'cancellation_abuse_window_hours';
    case CancellationCooldownMinutes = 'cancellation_cooldown_minutes';

    // Dispatch
    case DispatchRadiusStepsKm = 'dispatch_radius_steps_km';
    case DispatchBatchSize = 'dispatch_batch_size';
    case DispatchWaveIntervalSeconds = 'dispatch_wave_interval_seconds';
    case LocationStaleAfterSeconds = 'location_stale_after_seconds';
    case LocationMaxSpeedKmh = 'location_max_speed_kmh';
    case LocationHistoryIntervalSeconds = 'location_history_interval_seconds';
    case AutoRedispatchOnProviderCancel = 'auto_redispatch_on_provider_cancel';

    // Providers
    case ProviderSingleActiveJob = 'provider_single_active_job';
    case ProviderCashDebtLimitMinor = 'provider_cash_debt_limit_minor';
    case ProviderVerificationSlaHours = 'provider_verification_sla_hours';
    case ProviderDocumentExpiryWarningDays = 'provider_document_expiry_warning_days';

    // Finance
    case DefaultCommissionBasisPoints = 'default_commission_basis_points';
    case TaxRateBasisPoints = 'tax_rate_basis_points';
    case CustomerServiceFeeMinor = 'customer_service_fee_minor';
    case PaymentIntentTimeoutMinutes = 'payment_intent_timeout_minutes';
    case TipsEnabled = 'tips_enabled';
    case TipMaxBasisPoints = 'tip_max_basis_points';
    case RefundDualApprovalThresholdMinor = 'refund_dual_approval_threshold_minor';
    case PayoutMinAmountMinor = 'payout_min_amount_minor';
    case PayoutMaxAmountMinor = 'payout_max_amount_minor';
    case PayoutFeeFixedMinor = 'payout_fee_fixed_minor';
    case PayoutFeeBasisPoints = 'payout_fee_basis_points';
    case PayoutDualApprovalThresholdMinor = 'payout_dual_approval_threshold_minor';
    case CustomerFeeDebtLimitMinor = 'customer_fee_debt_limit_minor';

    // Safety & privacy
    case RidePinRequired = 'ride_pin_required';
    case ShareLinkTtlMinutes = 'share_link_ttl_minutes';
    case RidePinMaxAttempts = 'ride_pin_max_attempts';
    case DirectPhoneExposure = 'direct_phone_exposure';

    // Mobile apps
    case MinAppVersionCustomer = 'min_app_version_customer';
    case MinAppVersionDriver = 'min_app_version_driver';
    case ForceUpdateCustomer = 'force_update_customer';
    case ForceUpdateDriver = 'force_update_driver';
    case MaintenanceModeApps = 'maintenance_mode_apps';
    case FeatureWalletEnabled = 'feature_wallet_enabled';
    case FeatureOffersEnabled = 'feature_offers_enabled';

    // Admin security (moved from config in P1.2)
    case AdminMfaRequiredRoles = 'admin_mfa_required_roles';
    case AdminLoginLockoutSeconds = 'admin_login_lockout_seconds';
    case AdminIpAllowlist = 'admin_ip_allowlist';

    // Rate limits (moved from config in P1.2)
    case RateLimitApiPerMinute = 'rate_limit_api_per_minute';
    case RateLimitOtpRequestPerMinute = 'rate_limit_otp_request_per_minute';
    case RateLimitOtpRequestPerHour = 'rate_limit_otp_request_per_hour';
    case RateLimitOtpVerifyPerMinute = 'rate_limit_otp_verify_per_minute';
    case RateLimitLocationPerMinute = 'rate_limit_location_per_minute';
    case RateLimitOffersPerMinute = 'rate_limit_offers_per_minute';
    case RateLimitPublicFormsPerMinute = 'rate_limit_public_forms_per_minute';
    case RateLimitAdminLoginPerMinute = 'rate_limit_admin_login_per_minute';
    case RateLimitProviderUploadsPerMinute = 'rate_limit_provider_uploads_per_minute';
    case RateLimitPaymentWebhooksPerMinute = 'rate_limit_payment_webhooks_per_minute';

    public const VERSION_PATTERN = '/^\d{1,4}\.\d{1,4}\.\d{1,4}$/';

    public const PHONE_PATTERN = '/^\+?[0-9][0-9 ()\-]{4,23}$/';

    public function label(): string
    {
        return __("admin.settings.keys.{$this->value}.label");
    }

    public function descriptionKey(): string
    {
        return "admin.settings.keys.{$this->value}.description";
    }

    public function description(): string
    {
        return __($this->descriptionKey());
    }

    /**
     * DB-backed settings are never secrets (secrets stay in the environment, SRS §16.15).
     */
    public function isSensitive(): bool
    {
        return false;
    }

    public function type(): SettingType
    {
        return match ($this) {
            self::DefaultCountry, self::DefaultLocale, self::DefaultCurrency, self::DigitsPreference,
            self::SupportPhone, self::SupportEmail, self::SupportWhatsapp,
            self::MinAppVersionCustomer, self::MinAppVersionDriver, self::AdminIpAllowlist => SettingType::String,

            self::SupportedLocales, self::AdminMfaRequiredRoles => SettingType::StringList,

            self::DispatchRadiusStepsKm => SettingType::NumberList,

            self::RatingCustomerByProviderEnabled, self::AutoRedispatchOnProviderCancel, self::ProviderSingleActiveJob,
            self::RidePinRequired, self::TipsEnabled, self::DirectPhoneExposure, self::ForceUpdateCustomer, self::ForceUpdateDriver,
            self::MaintenanceModeApps, self::FeatureWalletEnabled, self::FeatureOffersEnabled => SettingType::Boolean,

            default => SettingType::Integer,
        };
    }

    public function group(): SettingGroup
    {
        return match ($this) {
            self::DefaultCountry, self::DefaultLocale, self::SupportedLocales, self::DefaultCurrency, self::DigitsPreference => SettingGroup::Localization,
            self::SupportPhone, self::SupportEmail, self::SupportWhatsapp => SettingGroup::Support,
            self::OtpLength, self::OtpTtlSeconds, self::OtpResendCooldownSeconds, self::OtpMaxAttempts => SettingGroup::Otp,
            self::BookingSearchTtlSeconds, self::OfferTtlSeconds, self::MaxOffersPerProviderPerBooking, self::MaxOfferRevisions,
            self::MaxActiveOnDemandBookings, self::ScheduledBookingMinLeadMinutes, self::ScheduledBookingMaxDays, self::ScheduledSearchLeadMinutes,
            self::RatingCustomerByProviderEnabled, self::RatingWindowHours, self::RatingMinSample, self::CancellationAbuseThreshold,
            self::CancellationAbuseWindowHours, self::CancellationCooldownMinutes => SettingGroup::Booking,
            self::DispatchRadiusStepsKm, self::DispatchBatchSize, self::DispatchWaveIntervalSeconds, self::LocationStaleAfterSeconds,
            self::LocationMaxSpeedKmh, self::LocationHistoryIntervalSeconds,
            self::AutoRedispatchOnProviderCancel => SettingGroup::Dispatch,
            self::ProviderSingleActiveJob, self::ProviderCashDebtLimitMinor, self::ProviderVerificationSlaHours,
            self::ProviderDocumentExpiryWarningDays => SettingGroup::Providers,
            self::DefaultCommissionBasisPoints, self::TaxRateBasisPoints, self::CustomerServiceFeeMinor, self::PaymentIntentTimeoutMinutes,
            self::TipsEnabled, self::TipMaxBasisPoints, self::RefundDualApprovalThresholdMinor, self::PayoutMinAmountMinor,
            self::PayoutMaxAmountMinor, self::PayoutFeeFixedMinor, self::PayoutFeeBasisPoints, self::PayoutDualApprovalThresholdMinor,
            self::CustomerFeeDebtLimitMinor => SettingGroup::Finance,
            self::RidePinRequired, self::ShareLinkTtlMinutes, self::RidePinMaxAttempts, self::DirectPhoneExposure => SettingGroup::Safety,
            self::MinAppVersionCustomer, self::MinAppVersionDriver, self::ForceUpdateCustomer, self::ForceUpdateDriver,
            self::MaintenanceModeApps, self::FeatureWalletEnabled, self::FeatureOffersEnabled => SettingGroup::Apps,
            self::AdminMfaRequiredRoles, self::AdminLoginLockoutSeconds, self::AdminIpAllowlist => SettingGroup::Security,
            default => SettingGroup::RateLimits,
        };
    }

    public function input(): SettingInput
    {
        return match ($this) {
            self::DefaultCountry, self::DefaultLocale, self::DefaultCurrency, self::DigitsPreference => SettingInput::Select,
            self::SupportedLocales, self::AdminMfaRequiredRoles => SettingInput::MultiSelect,
            self::SupportPhone, self::SupportWhatsapp => SettingInput::Phone,
            self::SupportEmail => SettingInput::Email,
            self::DispatchRadiusStepsKm => SettingInput::NumberList,
            self::ProviderCashDebtLimitMinor, self::CustomerServiceFeeMinor, self::RefundDualApprovalThresholdMinor, self::PayoutMinAmountMinor,
            self::PayoutMaxAmountMinor, self::PayoutFeeFixedMinor, self::PayoutDualApprovalThresholdMinor, self::CustomerFeeDebtLimitMinor => SettingInput::Money,
            self::MinAppVersionCustomer, self::MinAppVersionDriver, self::AdminIpAllowlist => SettingInput::Text,
            default => $this->type() === SettingType::Boolean ? SettingInput::Toggle : SettingInput::Number,
        };
    }

    /**
     * Whether an empty value is stored as null (optional contact details).
     */
    public function isNullable(): bool
    {
        return in_array($this, [self::SupportPhone, self::SupportEmail, self::SupportWhatsapp, self::AdminIpAllowlist], true);
    }

    /**
     * Unit suffix shown next to numeric inputs (a translation key under admin.settings.units).
     */
    public function unit(): ?string
    {
        return match ($this) {
            self::OtpTtlSeconds, self::OtpResendCooldownSeconds, self::BookingSearchTtlSeconds, self::OfferTtlSeconds,
            self::DispatchWaveIntervalSeconds, self::LocationStaleAfterSeconds, self::LocationHistoryIntervalSeconds, self::AdminLoginLockoutSeconds => 'seconds',
            self::ShareLinkTtlMinutes, self::ScheduledBookingMinLeadMinutes, self::ScheduledSearchLeadMinutes, self::PaymentIntentTimeoutMinutes,
            self::CancellationCooldownMinutes => 'minutes',
            self::ScheduledBookingMaxDays => 'days',
            self::ProviderVerificationSlaHours, self::RatingWindowHours, self::CancellationAbuseWindowHours => 'hours',
            self::ProviderDocumentExpiryWarningDays => 'days',
            self::DefaultCommissionBasisPoints, self::TaxRateBasisPoints, self::TipMaxBasisPoints, self::PayoutFeeBasisPoints => 'basis_points',
            self::DispatchRadiusStepsKm => 'km',
            self::LocationMaxSpeedKmh => 'kmh',
            self::RateLimitApiPerMinute, self::RateLimitOtpRequestPerMinute, self::RateLimitOtpVerifyPerMinute,
            self::RateLimitLocationPerMinute, self::RateLimitOffersPerMinute, self::RateLimitPublicFormsPerMinute,
            self::RateLimitAdminLoginPerMinute, self::RateLimitProviderUploadsPerMinute, self::RateLimitPaymentWebhooksPerMinute => 'per_minute',
            self::RateLimitOtpRequestPerHour => 'per_hour',
            default => null,
        };
    }

    /**
     * Inclusive bounds of integer settings (null for non-numeric ones).
     *
     * @return array{0: int, 1: int}|null
     */
    public function bounds(): ?array
    {
        return match ($this) {
            self::OtpLength => [4, 8],
            self::OtpTtlSeconds => [60, 1800],
            self::OtpResendCooldownSeconds => [10, 600],
            self::OtpMaxAttempts => [1, 10],
            self::BookingSearchTtlSeconds => [30, 3600],
            self::OfferTtlSeconds => [10, 1800],
            self::MaxOffersPerProviderPerBooking => [1, 10],
            self::MaxOfferRevisions => [0, 10],
            self::MaxActiveOnDemandBookings => [1, 10],
            self::ScheduledBookingMinLeadMinutes => [0, 1440],
            self::ScheduledBookingMaxDays => [1, 90],
            self::ScheduledSearchLeadMinutes => [1, 240],
            self::DispatchBatchSize => [1, 200],
            self::DispatchWaveIntervalSeconds => [5, 600],
            self::LocationStaleAfterSeconds => [10, 3600],
            self::LocationMaxSpeedKmh => [30, 1000],
            self::LocationHistoryIntervalSeconds => [1, 600],
            self::ProviderCashDebtLimitMinor, self::CustomerServiceFeeMinor, self::RefundDualApprovalThresholdMinor, self::PayoutMinAmountMinor,
            self::PayoutMaxAmountMinor, self::PayoutFeeFixedMinor, self::PayoutDualApprovalThresholdMinor, self::CustomerFeeDebtLimitMinor => [0, 100_000_000],
            self::PayoutFeeBasisPoints => [0, 5_000],
            self::PaymentIntentTimeoutMinutes => [5, 1440],
            self::TipMaxBasisPoints => [100, 100_000],
            self::RateLimitPaymentWebhooksPerMinute => [10, 100_000],
            self::DefaultCommissionBasisPoints, self::TaxRateBasisPoints => [0, 10_000],
            self::ShareLinkTtlMinutes => [5, 1440],
            self::RidePinMaxAttempts => [1, 10],
            self::RatingWindowHours => [1, 720],
            self::RatingMinSample => [1, 100],
            self::CancellationAbuseThreshold => [1, 50],
            self::CancellationAbuseWindowHours => [1, 720],
            self::CancellationCooldownMinutes => [1, 10_080],
            self::ProviderVerificationSlaHours => [1, 720],
            self::ProviderDocumentExpiryWarningDays => [1, 365],
            self::AdminLoginLockoutSeconds => [60, 86_400],
            self::RateLimitApiPerMinute, self::RateLimitLocationPerMinute, self::RateLimitOffersPerMinute => [1, 10_000],
            self::RateLimitProviderUploadsPerMinute => [1, 1000],
            self::RateLimitOtpRequestPerMinute, self::RateLimitOtpVerifyPerMinute, self::RateLimitPublicFormsPerMinute,
            self::RateLimitAdminLoginPerMinute => [1, 1000],
            self::RateLimitOtpRequestPerHour => [1, 10_000],
            default => null,
        };
    }

    /**
     * Default value (typed). Config-backed defaults keep env-driven deployments unchanged.
     */
    public function default(): mixed
    {
        return match ($this) {
            self::DefaultCountry => 'EG',
            self::DefaultLocale => Locale::default()->value,
            self::SupportedLocales => Locale::values(),
            self::DefaultCurrency => (string) config('admin.default_currency', 'EGP'),
            self::DigitsPreference => DigitsPreference::Auto->value,
            self::SupportPhone, self::SupportEmail, self::SupportWhatsapp => null,
            self::OtpLength => 6,
            self::OtpTtlSeconds => 300,
            self::OtpResendCooldownSeconds => 60,
            self::OtpMaxAttempts => 5,
            self::BookingSearchTtlSeconds => 300,
            self::OfferTtlSeconds => 120,
            self::MaxOffersPerProviderPerBooking => 1,
            self::MaxOfferRevisions => 2,
            self::MaxActiveOnDemandBookings => 1,
            self::ScheduledBookingMinLeadMinutes => 30,
            self::ScheduledBookingMaxDays => 14,
            self::ScheduledSearchLeadMinutes => 20,
            self::RatingCustomerByProviderEnabled => true,
            self::DispatchRadiusStepsKm => [2.0, 4.0, 7.0],
            self::DispatchBatchSize => 10,
            self::DispatchWaveIntervalSeconds => 30,
            self::LocationStaleAfterSeconds => 90,
            self::LocationMaxSpeedKmh => 200,
            self::LocationHistoryIntervalSeconds => 15,
            self::AutoRedispatchOnProviderCancel => true,
            self::ProviderSingleActiveJob => true,
            self::ProviderCashDebtLimitMinor => 50_000,
            self::DefaultCommissionBasisPoints => 1000,
            self::TaxRateBasisPoints, self::CustomerServiceFeeMinor => 0,
            self::PaymentIntentTimeoutMinutes => 30,
            self::TipsEnabled, self::FeatureWalletEnabled, self::FeatureOffersEnabled => true,
            self::TipMaxBasisPoints => 10_000,
            self::RefundDualApprovalThresholdMinor => 100_000,
            self::PayoutMinAmountMinor => 10_000,
            self::PayoutMaxAmountMinor => 5_000_000,
            self::PayoutFeeFixedMinor, self::PayoutFeeBasisPoints => 0,
            self::PayoutDualApprovalThresholdMinor => 1_000_000,
            self::ProviderVerificationSlaHours => 24,
            self::ProviderDocumentExpiryWarningDays => 30,
            self::RidePinRequired => true,
            self::ShareLinkTtlMinutes => 120,
            self::RidePinMaxAttempts => 5,
            self::RatingWindowHours => 72,
            self::RatingMinSample => 5,
            self::CancellationAbuseThreshold => 3,
            self::CancellationAbuseWindowHours => 24,
            self::CancellationCooldownMinutes => 60,
            self::CustomerFeeDebtLimitMinor => 0,
            self::DirectPhoneExposure => false,
            self::MinAppVersionCustomer, self::MinAppVersionDriver => '1.0.0',
            self::ForceUpdateCustomer, self::ForceUpdateDriver, self::MaintenanceModeApps => false,
            self::AdminMfaRequiredRoles => array_values(array_filter(
                (array) config('admin.auth.mfa.required_roles', []),
                static fn (mixed $role): bool => is_string($role) && AdminRole::tryFrom($role) !== null,
            )),
            self::AdminLoginLockoutSeconds => (int) config('admin.auth.login.lockout_seconds', 900),
            self::AdminIpAllowlist => null,
            self::RateLimitApiPerMinute => (int) config('rate_limits.api.per_minute', 120),
            self::RateLimitOtpRequestPerMinute => (int) config('rate_limits.otp-request.per_minute', 3),
            self::RateLimitOtpRequestPerHour => (int) config('rate_limits.otp-request.per_hour', 10),
            self::RateLimitOtpVerifyPerMinute => (int) config('rate_limits.otp-verify.per_minute', 5),
            self::RateLimitLocationPerMinute => (int) config('rate_limits.location.per_minute', 30),
            self::RateLimitOffersPerMinute => (int) config('rate_limits.offers.per_minute', 20),
            self::RateLimitPublicFormsPerMinute => (int) config('rate_limits.public-forms.per_minute', 5),
            self::RateLimitAdminLoginPerMinute => (int) config('rate_limits.admin-login.per_minute', 5),
            self::RateLimitProviderUploadsPerMinute => (int) config('rate_limits.provider-uploads.per_minute', 20),
            self::RateLimitPaymentWebhooksPerMinute => (int) config('rate_limits.payment-webhooks.per_minute', 600),
        };
    }

    /**
     * Rules for the (normalized, typed) value. List items are validated by itemRules().
     *
     * @return list<mixed>
     */
    public function rules(): array
    {
        $bounds = $this->bounds();

        return match ($this) {
            self::DefaultCountry => ['required', 'string', Rule::exists('countries', 'iso2')->where('active', true)],
            self::DefaultLocale => ['required', Rule::enum(Locale::class)],
            self::DefaultCurrency => ['required', 'string', Rule::exists('currencies', 'code')->where('active', true)],
            self::DigitsPreference => ['required', Rule::enum(DigitsPreference::class)],
            self::SupportedLocales => ['required', 'array', 'min:1'],
            self::AdminMfaRequiredRoles => ['present', 'array'],
            self::DispatchRadiusStepsKm => ['required', 'array', 'min:1', 'max:8'],
            self::SupportPhone, self::SupportWhatsapp => ['nullable', 'string', 'max:32', 'regex:'.self::PHONE_PATTERN],
            self::SupportEmail => ['nullable', 'string', 'max:191', 'email:rfc'],
            self::MinAppVersionCustomer, self::MinAppVersionDriver => ['required', 'string', 'regex:'.self::VERSION_PATTERN],
            self::AdminIpAllowlist => ['nullable', 'string', 'max:191', new AdminIpAllowlistRule],
            default => match ($this->type()) {
                SettingType::Boolean => ['required', 'boolean'],
                SettingType::Integer => ['required', 'integer', 'min:'.($bounds[0] ?? 0), 'max:'.($bounds[1] ?? PHP_INT_MAX)],
                default => ['required'],
            },
        };
    }

    /**
     * Rules for every element of a list setting (empty for scalars).
     *
     * @return list<mixed>
     */
    public function itemRules(): array
    {
        return match ($this) {
            self::SupportedLocales => ['string', Rule::enum(Locale::class)],
            self::AdminMfaRequiredRoles => ['string', Rule::enum(AdminRole::class)],
            self::DispatchRadiusStepsKm => ['numeric', 'gt:0', 'max:500'],
            default => [],
        };
    }

    /**
     * Static options of select settings; dynamic ones (countries, currencies) are resolved by the service.
     *
     * @return array<string, string>|null
     */
    public function staticOptions(): ?array
    {
        /** @var array<string, string>|null $options */
        $options = match ($this) {
            self::DefaultLocale, self::SupportedLocales => Locale::options(),
            self::DigitsPreference => DigitsPreference::options(),
            self::AdminMfaRequiredRoles => AdminRole::options(),
            default => null,
        };

        return $options;
    }

    /**
     * Whether the options come from the database (active countries / currencies).
     */
    public function hasDynamicOptions(): bool
    {
        return $this === self::DefaultCountry || $this === self::DefaultCurrency;
    }

    /**
     * Converts raw form input into the typed value (empty optional text becomes null, "2, 4 ,7" becomes a list).
     * Values that cannot be converted are returned unchanged so validation reports them.
     */
    public function fromInput(mixed $input): mixed
    {
        return match ($this->type()) {
            SettingType::Boolean => filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? ($input === null || $input === '' ? false : $input),
            SettingType::Integer => is_string($input) && preg_match('/^-?\d{1,18}$/', trim($input)) === 1 ? (int) trim($input) : $input,
            SettingType::String => $this->stringFromInput($input),
            SettingType::StringList => $this->stringListFromInput($input),
            SettingType::NumberList => $this->numberListFromInput($input),
        };
    }

    /**
     * Casts a stored JSON value back to the typed value; invalid stored data falls back to the default.
     */
    public function fromStored(mixed $stored): mixed
    {
        return match ($this->type()) {
            SettingType::Boolean => is_bool($stored) ? $stored : $this->default(),
            SettingType::Integer => is_int($stored) ? $stored : $this->default(),
            SettingType::String => is_string($stored) ? $stored : ($stored === null && $this->isNullable() ? null : $this->default()),
            SettingType::StringList => is_array($stored) ? array_values(array_filter($stored, 'is_string')) : $this->default(),
            SettingType::NumberList => is_array($stored) && $stored !== [] ? array_values(array_map(floatval(...), array_filter($stored, 'is_numeric'))) : $this->default(),
        };
    }

    private function stringFromInput(mixed $input): mixed
    {
        if ($input === null || is_string($input)) {
            $value = trim((string) $input);

            return $value === '' && $this->isNullable() ? null : $value;
        }

        return $input;
    }

    /**
     * @return mixed list<string>, or the original (invalid) input
     */
    private function stringListFromInput(mixed $input): mixed
    {
        if ($input === null || $input === '') {
            return [];
        }

        if (! is_array($input)) {
            return $input;
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): mixed => is_string($item) ? trim($item) : $item,
            $input,
        ), static fn (mixed $item): bool => $item !== '')));
    }

    /**
     * @return mixed list<float|string>, or the original (invalid) input
     */
    private function numberListFromInput(mixed $input): mixed
    {
        if ($input === null || $input === '') {
            return [];
        }

        $items = is_string($input) ? preg_split('/[\s,،;]+/u', trim($input), -1, PREG_SPLIT_NO_EMPTY) : $input;

        if (! is_array($items)) {
            return $input;
        }

        return array_values(array_map(
            static fn (mixed $item): mixed => is_string($item) && is_numeric(trim($item)) ? (float) trim($item) : $item,
            $items,
        ));
    }
}
