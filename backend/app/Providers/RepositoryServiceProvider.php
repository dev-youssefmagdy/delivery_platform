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
use App\Repositories\Contracts\Finance\FinancialPositionRepositoryInterface;
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
use App\Repositories\Eloquent\Finance\PreLedgerFinancialPositionRepository;
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
        BookingReportRepositoryInterface::class => BookingReportRepository::class,
        CancellationPolicyRepositoryInterface::class => CancellationPolicyRepository::class,
        RatingRepositoryInterface::class => RatingRepository::class,
        ShareLinkRepositoryInterface::class => ShareLinkRepository::class,
        // P2.4: the booking module's offer seam is now backed by the real offer tables (ADR-94).
        OfferRepositoryInterface::class => OfferRepository::class,
        BookingOfferRepositoryInterface::class => BookingOfferBridge::class,
        FarePolicyRepositoryInterface::class => FarePolicyRepository::class,
        // Neutral until the ledger exists: P3.1 rebinds this to the ledger-backed repository (ADR-92).
        FinancialPositionRepositoryInterface::class => PreLedgerFinancialPositionRepository::class,
    ];
}
