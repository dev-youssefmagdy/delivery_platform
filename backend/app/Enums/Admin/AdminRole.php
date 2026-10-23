<?php

namespace App\Enums\Admin;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The 12 SRS §4.2 admin roles. Values are the stable `roles.name` of the seeded system roles; their default
 * permission sets are Plan Appendix B (editable afterwards in the admin panel, except Super Admin).
 */
enum AdminRole: string implements HasLabel
{
    use EnumHelpers;

    case SuperAdmin = 'super_admin';
    case OperationsAdmin = 'operations_admin';
    case DispatchAgent = 'dispatch_agent';
    case DriverVerificationOfficer = 'driver_verification_officer';
    case CustomerSupportAgent = 'customer_support_agent';
    case SafetyIncidentOfficer = 'safety_incident_officer';
    case FinanceManager = 'finance_manager';
    case FinancePayoutOfficer = 'finance_payout_officer';
    case MarketingCouponManager = 'marketing_coupon_manager';
    case ContentCmsEditor = 'content_cms_editor';
    case ReadOnlyAuditor = 'read_only_auditor';
    case SystemIntegrationAdmin = 'system_integration_admin';

    public function label(): string
    {
        return __("enums.admin_role.{$this->value}");
    }

    /**
     * Super Admin passes every check through Gate::before and has no stored permissions.
     */
    public function hasAllPermissions(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * Default permissions (Plan Appendix B). Interpretations are recorded in docs/DECISIONS.md.
     *
     * @return list<AdminPermission>
     */
    public function defaultPermissions(): array
    {
        $p = AdminPermission::class;

        return match ($this) {
            self::SuperAdmin => [],
            self::OperationsAdmin => [
                $p::DashboardView,
                $p::CustomersView, $p::CustomersViewPii, $p::CustomersUpdate, $p::CustomersSuspend, $p::CustomersProcessDeletion,
                $p::DriversView, $p::DriversSuspend, $p::VehiclesView,
                $p::BookingsView, $p::BookingsManage, $p::BookingsCancel, $p::BookingsAssignOverride, $p::BookingsPinOverride, $p::BookingsViewPii,
                $p::OffersView, $p::OffersModerate,
                $p::ServicesManage, $p::VehicleCategoriesManage, $p::ServiceAreasManage, $p::FarePoliciesManage, $p::CancellationPoliciesManage, $p::PoliciesManage,
                $p::SupportView, $p::IncidentsView, $p::ReportsView,
                $p::ReviewsView, $p::ReviewsModerate, $p::RiskView, $p::RiskManage, $p::LeadsView, $p::LeadsManage,
            ],
            self::DispatchAgent => [
                $p::DashboardView, $p::BookingsView, $p::BookingsManage, $p::BookingsCancel, $p::OffersView, $p::DriversView, $p::CustomersView,
            ],
            self::DriverVerificationOfficer => [
                $p::DriversView, $p::DriversVerify, $p::DriversReject, $p::DocumentsView, $p::VehiclesView, $p::VehiclesApprove, $p::VehiclesReject,
            ],
            self::CustomerSupportAgent => [
                $p::CustomersView, $p::BookingsView, $p::SupportView, $p::SupportManage, $p::MacrosManage, $p::RefundsRequest,
            ],
            self::SafetyIncidentOfficer => [
                $p::IncidentsView, $p::IncidentsManage, $p::IncidentsViewEvidence, $p::IncidentsExportRestricted,
                $p::BookingsView, $p::BookingsViewPii, $p::CustomersViewPii, $p::DriversViewPii,
            ],
            self::FinanceManager => [
                $p::FinanceView, $p::FinanceReconcile, $p::PaymentsView, $p::RefundsApprove,
                $p::PayoutsView, $p::PayoutsReview, $p::PayoutsApprove,
                $p::WalletsView, $p::WalletsAdjust, $p::WalletsApproveAdjustment, $p::LedgerView,
                $p::CommissionRulesManage, $p::ReportsView, $p::ReportsExport,
            ],
            self::FinancePayoutOfficer => [$p::PayoutsView, $p::PayoutsReview, $p::WalletsView, $p::LedgerView],
            self::MarketingCouponManager => [
                $p::CouponsView, $p::CouponsManage, $p::CouponsExport, $p::CampaignsManage, $p::PushCampaignsManage, $p::ReportsView,
            ],
            self::ContentCmsEditor => [$p::ContentManage, $p::TranslationsManage, $p::LeadsView, $p::LeadsManage],
            self::ReadOnlyAuditor => array_values(array_filter(
                AdminPermission::cases(),
                static fn (AdminPermission $permission): bool => $permission->action() === 'view' && ! in_array($permission->group(), ['demo_items', 'otp_challenges'], true),
            )),
            self::SystemIntegrationAdmin => [
                $p::SettingsView, $p::SettingsManage,
                $p::SystemHealth, $p::SystemFeatureFlags, $p::SystemAppVersions, $p::SystemQueues,
                $p::NotificationsManage, $p::AdminsView, $p::OtpChallengesView,
            ],
        };
    }
}
