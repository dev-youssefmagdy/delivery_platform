<?php

namespace App\Providers;

use App\Repositories\Contracts\Admin\AdminExportRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminLoginEventRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminSessionRepositoryInterface;
use App\Repositories\Contracts\Admin\RoleRepositoryInterface;
use App\Repositories\Contracts\Audit\AuditLogRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingOfferRepositoryInterface;
use App\Repositories\Contracts\Booking\OfferRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingProofRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingAttachmentRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingContactRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingItemRepositoryInterface;
use App\Repositories\Contracts\Booking\DeliveryAttemptRepositoryInterface;
use App\Repositories\Contracts\Booking\RecipientOtpRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingReportRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStatusEventRepositoryInterface;
use App\Repositories\Contracts\Booking\CancellationPolicyRepositoryInterface;
use App\Repositories\Contracts\Booking\RatingRepositoryInterface;
use App\Repositories\Contracts\Booking\ShareLinkRepositoryInterface;
use App\Repositories\Contracts\Booking\BookingStopRepositoryInterface;
use App\Repositories\Contracts\Catalog\CatalogUsageRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceCategoryVersionRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFamilyRepositoryInterface;
use App\Repositories\Contracts\Catalog\ServiceFormSchemaRepositoryInterface;
use App\Repositories\Contracts\Catalog\VehicleCategoryRepositoryInterface;
use App\Repositories\Contracts\Common\CountryRepositoryInterface;
use App\Repositories\Contracts\Common\CurrencyRepositoryInterface;
use App\Repositories\Contracts\Common\IdempotencyKeyRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerActivityRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerMessageRepositoryInterface;
use App\Repositories\Contracts\Customers\CustomerRepositoryInterface;
use App\Repositories\Contracts\Demo\DemoItemRepositoryInterface;
use App\Repositories\Contracts\Dispatch\BookingParticipationRepositoryInterface;
use App\Repositories\Contracts\Dispatch\DispatchRecipientRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponAttemptRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponRepositoryInterface;
use App\Repositories\Contracts\Coupon\CouponReservationRepositoryInterface;
use App\Repositories\Contracts\Finance\CommissionRuleRepositoryInterface;
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalEntryRepositoryInterface;
use App\Repositories\Contracts\Finance\JournalLineRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerAccountRepositoryInterface;
use App\Repositories\Contracts\Finance\LedgerReconciliationRunRepositoryInterface;
use App\Repositories\Contracts\Finance\PaymentPositionRepositoryInterface;
use App\Repositories\Contracts\Finance\FinancialAdjustmentRepositoryInterface;
use App\Repositories\Eloquent\Finance\FinancialAdjustmentRepository;
use App\Repositories\Contracts\Finance\CashReconciliationRepositoryInterface;
use App\Repositories\Eloquent\Finance\CashReconciliationRepository;
use App\Repositories\Contracts\Finance\ProviderEarningsRepositoryInterface;
use App\Repositories\Eloquent\Finance\ProviderEarningsRepository;
use App\Repositories\Contracts\Payout\ProviderPayoutMethodRepositoryInterface;
use App\Repositories\Eloquent\Payout\ProviderPayoutMethodRepository;
use App\Repositories\Contracts\Payout\PayoutRequestRepositoryInterface;
use App\Repositories\Eloquent\Payout\PayoutRequestRepository;
use App\Repositories\Contracts\Payout\PayoutEventRepositoryInterface;
use App\Repositories\Eloquent\Payout\PayoutEventRepository;
use App\Repositories\Contracts\Payout\PayoutBatchRepositoryInterface;
use App\Repositories\Eloquent\Payout\PayoutBatchRepository;
use App\Repositories\Contracts\Payments\GatewayWebhookEventRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentEventRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentReconciliationRunRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRefundRepositoryInterface;
use App\Repositories\Contracts\Payments\PaymentRepositoryInterface;
use App\Repositories\Eloquent\Payments\GatewayWebhookEventRepository;
use App\Repositories\Eloquent\Payments\PaymentEventRepository;
use App\Repositories\Eloquent\Payments\PaymentMethodRepository;
use App\Repositories\Eloquent\Payments\PaymentReconciliationRunRepository;
use App\Repositories\Eloquent\Payments\PaymentRefundRepository;
use App\Repositories\Eloquent\Payments\PaymentRepository;
use App\Repositories\Contracts\Geography\CityRepositoryInterface;
use App\Repositories\Contracts\Geography\SavedPlaceRepositoryInterface;
use App\Repositories\Contracts\Geography\ServiceZoneRepositoryInterface;
use App\Repositories\Contracts\Geography\ZoneServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\Identity\AccessTokenRepositoryInterface;
use App\Repositories\Contracts\Identity\AccountDeletionRequestRepositoryInterface;
use App\Repositories\Contracts\Identity\CustomerProfileRepositoryInterface;
use App\Repositories\Contracts\Identity\OtpChallengeRepositoryInterface;
use App\Repositories\Contracts\Identity\UserConsentRepositoryInterface;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use App\Repositories\Contracts\Identity\UserRepositoryInterface;
use App\Repositories\Contracts\Location\ProviderLastLocationRepositoryInterface;
use App\Repositories\Contracts\Location\ProviderLocationRepositoryInterface;
use App\Repositories\Contracts\Operations\OperationsMapRepositoryInterface;
use App\Repositories\Contracts\Policy\PolicyVersionRepositoryInterface;
use App\Repositories\Contracts\Pricing\FarePolicyRepositoryInterface;
use App\Repositories\Contracts\Provider\DocumentRequirementRepositoryInterface;
use App\Repositories\Contracts\Provider\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderActivityRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderProfileRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderServiceRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderStatusEventRepositoryInterface;
use App\Repositories\Contracts\Provider\ProviderWorkloadRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleDocumentRepositoryInterface;
use App\Repositories\Contracts\Provider\VehiclePhotoRepositoryInterface;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Repositories\Contracts\Settings\SettingsRepositoryInterface;
use App\Repositories\Eloquent\Admin\AdminExportRepository;
use App\Repositories\Eloquent\Admin\AdminLoginEventRepository;
use App\Repositories\Eloquent\Admin\AdminRepository;
use App\Repositories\Eloquent\Admin\AdminSessionRepository;
use App\Repositories\Eloquent\Admin\RoleRepository;
use App\Repositories\Eloquent\Audit\AuditLogRepository;
use App\Repositories\Eloquent\Booking\BookingRepository;
use App\Repositories\Eloquent\Booking\BookingProofRepository;
use App\Repositories\Eloquent\Booking\BookingAttachmentRepository;
use App\Repositories\Eloquent\Booking\BookingContactRepository;
use App\Repositories\Eloquent\Booking\BookingItemRepository;
use App\Repositories\Eloquent\Booking\DeliveryAttemptRepository;
use App\Repositories\Eloquent\Booking\RecipientOtpRepository;
use App\Repositories\Eloquent\Booking\BookingReportRepository;
use App\Repositories\Eloquent\Booking\BookingStatusEventRepository;
use App\Repositories\Eloquent\Booking\CancellationPolicyRepository;
use App\Repositories\Eloquent\Booking\RatingRepository;
use App\Repositories\Eloquent\Booking\ShareLinkRepository;
use App\Repositories\Eloquent\Booking\BookingStopRepository;
use App\Repositories\Eloquent\Booking\OfferRepository;
use App\Repositories\Eloquent\Catalog\CatalogUsageRepository;
use App\Repositories\Eloquent\Catalog\ServiceCategoryRepository;
use App\Repositories\Eloquent\Catalog\ServiceCategoryVersionRepository;
use App\Repositories\Eloquent\Catalog\ServiceFamilyRepository;
use App\Repositories\Eloquent\Catalog\ServiceFormSchemaRepository;
use App\Repositories\Eloquent\Catalog\VehicleCategoryRepository;
use App\Repositories\Eloquent\Common\CountryRepository;
use App\Repositories\Eloquent\Common\CurrencyRepository;
use App\Repositories\Eloquent\Common\IdempotencyKeyRepository;
use App\Repositories\Eloquent\Customers\CustomerActivityRepository;
use App\Repositories\Eloquent\Customers\CustomerMessageRepository;
use App\Repositories\Eloquent\Customers\CustomerRepository;
use App\Repositories\Eloquent\Demo\DemoItemRepository;
use App\Repositories\Eloquent\Dispatch\BookingParticipationRepository;
use App\Repositories\Eloquent\Operations\OperationsMapRepository;
use App\Repositories\Eloquent\Dispatch\DispatchRecipientRepository;
use App\Repositories\Eloquent\Coupon\CouponAttemptRepository;
use App\Repositories\Eloquent\Coupon\CouponRepository;
use App\Repositories\Eloquent\Coupon\CouponReservationRepository;
use App\Repositories\Eloquent\Finance\CommissionRuleRepository;
use App\Repositories\Eloquent\Finance\JournalEntryRepository;
use App\Repositories\Eloquent\Finance\JournalLineRepository;
use App\Repositories\Eloquent\Finance\LedgerAccountRepository;
use App\Repositories\Eloquent\Finance\LedgerFinancialPositionRepository;
use App\Repositories\Eloquent\Finance\LedgerReconciliationRunRepository;
use App\Repositories\Eloquent\Finance\PaymentPositionRepository;
use App\Repositories\Eloquent\Geography\CityRepository;
use App\Repositories\Eloquent\Geography\SavedPlaceRepository;
use App\Repositories\Eloquent\Geography\ServiceZoneRepository;
use App\Repositories\Eloquent\Geography\ZoneServiceCategoryRepository;
use App\Repositories\Eloquent\Identity\AccessTokenRepository;
use App\Repositories\Eloquent\Identity\AccountDeletionRequestRepository;
use App\Repositories\Eloquent\Identity\CustomerProfileRepository;
use App\Repositories\Eloquent\Identity\OtpChallengeRepository;
use App\Repositories\Eloquent\Identity\UserConsentRepository;
use App\Repositories\Eloquent\Identity\UserDeviceRepository;
use App\Repositories\Eloquent\Identity\UserRepository;
use App\Repositories\Eloquent\Location\ProviderLastLocationRepository;
use App\Repositories\Eloquent\Location\ProviderLocationRepository;
use App\Repositories\Eloquent\Policy\PolicyVersionRepository;
use App\Repositories\Eloquent\Pricing\FarePolicyRepository;
use App\Repositories\Eloquent\Provider\DocumentRequirementRepository;
use App\Repositories\Eloquent\Provider\DocumentTypeRepository;
use App\Repositories\Eloquent\Provider\ProviderActivityRepository;
use App\Repositories\Eloquent\Provider\ProviderDocumentRepository;
use App\Repositories\Eloquent\Provider\ProviderProfileRepository;
use App\Repositories\Eloquent\Provider\ProviderServiceRepository;
use App\Repositories\Eloquent\Provider\ProviderStatusEventRepository;
use App\Repositories\Eloquent\Provider\ProviderWorkloadRepository;
use App\Repositories\Eloquent\Provider\VehicleDocumentRepository;
use App\Repositories\Eloquent\Provider\VehiclePhotoRepository;
use App\Repositories\Eloquent\Provider\VehicleRepository;
use App\Repositories\Eloquent\Settings\SettingsRepository;
use App\Repositories\Contracts\Notification\AppNotificationRepositoryInterface;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use App\Repositories\Contracts\Notification\NotificationPreferenceRepositoryInterface;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;
use App\Repositories\Contracts\Notification\PushCampaignRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Repositories\Contracts\Reporting\MetricsAggregationRepositoryInterface;
use App\Repositories\Contracts\Reporting\ReportingRepositoryInterface;
use App\Repositories\Contracts\Reporting\ReportQueryRepositoryInterface;
use App\Repositories\Contracts\Lead\LeadRepositoryInterface;
use App\Repositories\Contracts\System\AppVersionRepositoryInterface;
use App\Repositories\Contracts\System\FailedJobRepositoryInterface;
use App\Repositories\Contracts\System\FeatureFlagRepositoryInterface;
use App\Repositories\Contracts\System\WebhookFailureRepositoryInterface;
use App\Repositories\Eloquent\System\AppVersionRepository;
use App\Repositories\Eloquent\System\FailedJobRepository;
use App\Repositories\Eloquent\System\FeatureFlagRepository;
use App\Repositories\Eloquent\System\WebhookFailureRepository;
use App\Repositories\Contracts\Risk\RiskDataRepositoryInterface;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Repositories\Contracts\Safety\IncidentThreadRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Repositories\Contracts\Safety\TrustedContactRepositoryInterface;
use App\Repositories\Contracts\Support\BookingMessageRepositoryInterface;
use App\Repositories\Contracts\Support\SupportMacroRepositoryInterface;
use App\Repositories\Contracts\Support\SupportTicketRepositoryInterface;
use App\Repositories\Contracts\Support\TicketThreadRepositoryInterface;
use App\Repositories\Eloquent\Reviews\RatingModerationRepository;
use App\Repositories\Eloquent\Reporting\MetricsAggregationRepository;
use App\Repositories\Eloquent\Reporting\ReportingRepository;
use App\Repositories\Eloquent\Reporting\ReportQueryRepository;
use App\Repositories\Eloquent\Lead\LeadRepository;
use App\Repositories\Eloquent\Risk\RiskDataRepository;
use App\Repositories\Eloquent\Risk\RiskSignalRepository;
use App\Repositories\Eloquent\Safety\IncidentThreadRepository;
use App\Repositories\Eloquent\Safety\SafetyIncidentRepository;
use App\Repositories\Eloquent\Safety\TrustedContactRepository;
use App\Repositories\Eloquent\Support\BookingMessageRepository;
use App\Repositories\Eloquent\Support\SupportMacroRepository;
use App\Repositories\Eloquent\Support\SupportTicketRepository;
use App\Repositories\Eloquent\Support\TicketThreadRepository;
use App\Repositories\Eloquent\Notification\AppNotificationRepository;
use App\Repositories\Eloquent\Notification\NotificationDeliveryRepository;
use App\Repositories\Eloquent\Notification\NotificationPreferenceRepository;
use App\Repositories\Eloquent\Notification\NotificationTemplateRepository;
use App\Repositories\Eloquent\Notification\PushCampaignRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repository interface → Eloquent implementation map. `make:admin-module` (P0.3) appends to $bindings.
 */
use App\Services\Offers\BookingOfferBridge;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AdminExportRepositoryInterface::class => AdminExportRepository::class,
        AdminLoginEventRepositoryInterface::class => AdminLoginEventRepository::class,
        AdminRepositoryInterface::class => AdminRepository::class,
        AdminSessionRepositoryInterface::class => AdminSessionRepository::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        AuditLogRepositoryInterface::class => AuditLogRepository::class,
        CountryRepositoryInterface::class => CountryRepository::class,
        CurrencyRepositoryInterface::class => CurrencyRepository::class,
        IdempotencyKeyRepositoryInterface::class => IdempotencyKeyRepository::class,
        DemoItemRepositoryInterface::class => DemoItemRepository::class,
        SettingsRepositoryInterface::class => SettingsRepository::class,
        PolicyVersionRepositoryInterface::class => PolicyVersionRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        CustomerProfileRepositoryInterface::class => CustomerProfileRepository::class,
        OtpChallengeRepositoryInterface::class => OtpChallengeRepository::class,
        UserDeviceRepositoryInterface::class => UserDeviceRepository::class,
        UserConsentRepositoryInterface::class => UserConsentRepository::class,
        AccountDeletionRequestRepositoryInterface::class => AccountDeletionRequestRepository::class,
        AccessTokenRepositoryInterface::class => AccessTokenRepository::class,
        CatalogUsageRepositoryInterface::class => CatalogUsageRepository::class,
        ServiceCategoryRepositoryInterface::class => ServiceCategoryRepository::class,
        ServiceCategoryVersionRepositoryInterface::class => ServiceCategoryVersionRepository::class,
        ServiceFamilyRepositoryInterface::class => ServiceFamilyRepository::class,
        ServiceFormSchemaRepositoryInterface::class => ServiceFormSchemaRepository::class,
        VehicleCategoryRepositoryInterface::class => VehicleCategoryRepository::class,
        CityRepositoryInterface::class => CityRepository::class,
        ServiceZoneRepositoryInterface::class => ServiceZoneRepository::class,
        ZoneServiceCategoryRepositoryInterface::class => ZoneServiceCategoryRepository::class,
        SavedPlaceRepositoryInterface::class => SavedPlaceRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        CustomerMessageRepositoryInterface::class => CustomerMessageRepository::class,
        CustomerActivityRepositoryInterface::class => CustomerActivityRepository::class,
        DocumentRequirementRepositoryInterface::class => DocumentRequirementRepository::class,
        DocumentTypeRepositoryInterface::class => DocumentTypeRepository::class,
        ProviderActivityRepositoryInterface::class => ProviderActivityRepository::class,
        ProviderDocumentRepositoryInterface::class => ProviderDocumentRepository::class,
        ProviderProfileRepositoryInterface::class => ProviderProfileRepository::class,
        ProviderServiceRepositoryInterface::class => ProviderServiceRepository::class,
        ProviderStatusEventRepositoryInterface::class => ProviderStatusEventRepository::class,
        VehicleDocumentRepositoryInterface::class => VehicleDocumentRepository::class,
        VehiclePhotoRepositoryInterface::class => VehiclePhotoRepository::class,
        VehicleRepositoryInterface::class => VehicleRepository::class,
        CouponRepositoryInterface::class => CouponRepository::class,
        CouponReservationRepositoryInterface::class => CouponReservationRepository::class,
        CouponAttemptRepositoryInterface::class => CouponAttemptRepository::class,
        NotificationTemplateRepositoryInterface::class => NotificationTemplateRepository::class,
        AppNotificationRepositoryInterface::class => AppNotificationRepository::class,
        NotificationDeliveryRepositoryInterface::class => NotificationDeliveryRepository::class,
        NotificationPreferenceRepositoryInterface::class => NotificationPreferenceRepository::class,
        PushCampaignRepositoryInterface::class => PushCampaignRepository::class,
        BookingMessageRepositoryInterface::class => BookingMessageRepository::class,
        SafetyIncidentRepositoryInterface::class => SafetyIncidentRepository::class,
        IncidentThreadRepositoryInterface::class => IncidentThreadRepository::class,
        TrustedContactRepositoryInterface::class => TrustedContactRepository::class,
        SupportMacroRepositoryInterface::class => SupportMacroRepository::class,
        SupportTicketRepositoryInterface::class => SupportTicketRepository::class,
        TicketThreadRepositoryInterface::class => TicketThreadRepository::class,
        RatingModerationRepositoryInterface::class => RatingModerationRepository::class,
        RiskSignalRepositoryInterface::class => RiskSignalRepository::class,
        LeadRepositoryInterface::class => LeadRepository::class,
        AppVersionRepositoryInterface::class => AppVersionRepository::class,
        FeatureFlagRepositoryInterface::class => FeatureFlagRepository::class,
        FailedJobRepositoryInterface::class => FailedJobRepository::class,
        WebhookFailureRepositoryInterface::class => WebhookFailureRepository::class,
        RiskDataRepositoryInterface::class => RiskDataRepository::class,
        MetricsAggregationRepositoryInterface::class => MetricsAggregationRepository::class,
        ReportingRepositoryInterface::class => ReportingRepository::class,
        ReportQueryRepositoryInterface::class => ReportQueryRepository::class,
        \App\Repositories\Contracts\Content\ArticleRepositoryInterface::class => \App\Repositories\Eloquent\Content\ArticleRepository::class,
        \App\Repositories\Contracts\Content\BannerRepositoryInterface::class => \App\Repositories\Eloquent\Content\BannerRepository::class,
        \App\Repositories\Contracts\Content\FaqRepositoryInterface::class => \App\Repositories\Eloquent\Content\FaqRepository::class,
        \App\Repositories\Contracts\Content\MenuRepositoryInterface::class => \App\Repositories\Eloquent\Content\MenuRepository::class,
        \App\Repositories\Contracts\Content\PageRepositoryInterface::class => \App\Repositories\Eloquent\Content\PageRepository::class,
        \App\Repositories\Contracts\Content\ContentAppLinkRepositoryInterface::class => \App\Repositories\Eloquent\Content\ContentAppLinkRepository::class,
        \App\Repositories\Contracts\Content\ContentCategoryRepositoryInterface::class => \App\Repositories\Eloquent\Content\ContentCategoryRepository::class,
        \App\Repositories\Contracts\Content\ContentMediaRepositoryInterface::class => \App\Repositories\Eloquent\Content\ContentMediaRepository::class,
        \App\Repositories\Contracts\Content\ContentRevisionRepositoryInterface::class => \App\Repositories\Eloquent\Content\ContentRevisionRepository::class,
        \App\Repositories\Contracts\Content\ContentReadRepositoryInterface::class => \App\Repositories\Eloquent\Content\ContentReadRepository::class,
        // @admin-module:bindings
        ProviderWorkloadRepositoryInterface::class => ProviderWorkloadRepository::class,
        ProviderLocationRepositoryInterface::class => ProviderLocationRepository::class,
        ProviderLastLocationRepositoryInterface::class => ProviderLastLocationRepository::class,
        DispatchRecipientRepositoryInterface::class => DispatchRecipientRepository::class,
        BookingParticipationRepositoryInterface::class => BookingParticipationRepository::class,
        OperationsMapRepositoryInterface::class => OperationsMapRepository::class,
        BookingRepositoryInterface::class => BookingRepository::class,
        BookingStopRepositoryInterface::class => BookingStopRepository::class,
        BookingStatusEventRepositoryInterface::class => BookingStatusEventRepository::class,
        BookingProofRepositoryInterface::class => BookingProofRepository::class,
        BookingContactRepositoryInterface::class => BookingContactRepository::class,
        BookingItemRepositoryInterface::class => BookingItemRepository::class,
        BookingAttachmentRepositoryInterface::class => BookingAttachmentRepository::class,
        RecipientOtpRepositoryInterface::class => RecipientOtpRepository::class,
        DeliveryAttemptRepositoryInterface::class => DeliveryAttemptRepository::class,
        BookingReportRepositoryInterface::class => BookingReportRepository::class,
        CancellationPolicyRepositoryInterface::class => CancellationPolicyRepository::class,
        RatingRepositoryInterface::class => RatingRepository::class,
        ShareLinkRepositoryInterface::class => ShareLinkRepository::class,
        // P2.4: the booking module's offer seam is now backed by the real offer tables (ADR-94).
        OfferRepositoryInterface::class => OfferRepository::class,
        BookingOfferRepositoryInterface::class => BookingOfferBridge::class,
        FarePolicyRepositoryInterface::class => FarePolicyRepository::class,
        // P3.1: the financial position is read from the ledger (ADR-115); P3.2 backs its payments part with the payments tables (ADR-116).
        FinancialPositionRepositoryInterface::class => LedgerFinancialPositionRepository::class,
        PaymentPositionRepositoryInterface::class => PaymentPositionRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        PaymentMethodRepositoryInterface::class => PaymentMethodRepository::class,
        PaymentEventRepositoryInterface::class => PaymentEventRepository::class,
        GatewayWebhookEventRepositoryInterface::class => GatewayWebhookEventRepository::class,
        PaymentRefundRepositoryInterface::class => PaymentRefundRepository::class,
        FinancialAdjustmentRepositoryInterface::class => FinancialAdjustmentRepository::class,
        \App\Repositories\Contracts\Booking\BookingAdjustmentRepositoryInterface::class => \App\Repositories\Eloquent\Booking\BookingAdjustmentRepository::class,
        \App\Repositories\Contracts\Booking\BookingReceiptRepositoryInterface::class => \App\Repositories\Eloquent\Booking\BookingReceiptRepository::class,
        CashReconciliationRepositoryInterface::class => CashReconciliationRepository::class,
        ProviderEarningsRepositoryInterface::class => ProviderEarningsRepository::class,
        ProviderPayoutMethodRepositoryInterface::class => ProviderPayoutMethodRepository::class,
        PayoutRequestRepositoryInterface::class => PayoutRequestRepository::class,
        PayoutEventRepositoryInterface::class => PayoutEventRepository::class,
        PayoutBatchRepositoryInterface::class => PayoutBatchRepository::class,
        PaymentReconciliationRunRepositoryInterface::class => PaymentReconciliationRunRepository::class,
        LedgerAccountRepositoryInterface::class => LedgerAccountRepository::class,
        JournalEntryRepositoryInterface::class => JournalEntryRepository::class,
        JournalLineRepositoryInterface::class => JournalLineRepository::class,
        LedgerReconciliationRunRepositoryInterface::class => LedgerReconciliationRunRepository::class,
        CommissionRuleRepositoryInterface::class => CommissionRuleRepository::class,
    ];
}
