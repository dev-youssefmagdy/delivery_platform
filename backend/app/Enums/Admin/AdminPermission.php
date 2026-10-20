<?php

namespace App\Enums\Admin;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Admin permission catalog (Plan Appendix A). Values are Spatie permission names on the `admin` guard (seeded
 * in P1.1). Format: `<group>.<action>`; the label is composed from both parts so new modules only add a group
 * label (`enums.admin_permission_group.<group>`). make:admin-module appends cases above the marker.
 */
enum AdminPermission: string implements HasLabel
{
    use EnumHelpers;

    case DashboardView = 'dashboard.view';
    case AdminsView = 'admins.view';
    case AdminsManage = 'admins.manage';
    case RolesView = 'roles.view';
    case RolesManage = 'roles.manage';
    case AuditLogsView = 'audit_logs.view';
    case LoginActivityView = 'login_activity.view';
    case SettingsView = 'settings.view';
    case SettingsManage = 'settings.manage';
    case MarketsManage = 'markets.manage';
    case PoliciesManage = 'policies.manage';
    case PoliciesPublish = 'policies.publish';
    case CustomersView = 'customers.view';
    case CustomersViewPii = 'customers.view_pii';
    case CustomersUpdate = 'customers.update';
    case CustomersSuspend = 'customers.suspend';
    case CustomersExport = 'customers.export';
    case CustomersProcessDeletion = 'customers.process_deletion';
    case DriversView = 'drivers.view';
    case DriversViewPii = 'drivers.view_pii';
    case DriversVerify = 'drivers.verify';
    case DriversReject = 'drivers.reject';
    case DriversSuspend = 'drivers.suspend';
    case DocumentsView = 'documents.view';
    case DocumentsManageTypes = 'documents.manage_types';
    case VehiclesView = 'vehicles.view';
    case VehiclesApprove = 'vehicles.approve';
    case VehiclesReject = 'vehicles.reject';
    case ServicesManage = 'services.manage';
    case VehicleCategoriesManage = 'vehicle_categories.manage';
    case ServiceAreasManage = 'service_areas.manage';
    case FarePoliciesManage = 'fare_policies.manage';
    case CommissionRulesManage = 'commission_rules.manage';
    case CancellationPoliciesManage = 'cancellation_policies.manage';
    case BookingsView = 'bookings.view';
    case BookingsManage = 'bookings.manage';
    case BookingsCancel = 'bookings.cancel';
    case BookingsAssignOverride = 'bookings.assign_override';
    case BookingsPinOverride = 'bookings.pin_override';
    case BookingsViewPii = 'bookings.view_pii';
    case OffersView = 'offers.view';
    case OffersModerate = 'offers.moderate';
    case CouponsView = 'coupons.view';
    case CouponsManage = 'coupons.manage';
    case CouponsExport = 'coupons.export';
    case CampaignsManage = 'campaigns.manage';
    case FinanceView = 'finance.view';
    case FinanceReconcile = 'finance.reconcile';
    case PaymentsView = 'payments.view';
    case RefundsRequest = 'refunds.request';
    case RefundsApprove = 'refunds.approve';
    case PayoutsView = 'payouts.view';
    case PayoutsReview = 'payouts.review';
    case PayoutsApprove = 'payouts.approve';
    case WalletsView = 'wallets.view';
    case WalletsAdjust = 'wallets.adjust';
    case WalletsApproveAdjustment = 'wallets.approve_adjustment';
    case LedgerView = 'ledger.view';
    case IncidentsView = 'incidents.view';
    case IncidentsManage = 'incidents.manage';
    case IncidentsViewEvidence = 'incidents.view_evidence';
    case IncidentsExportRestricted = 'incidents.export_restricted';
    case SupportView = 'support.view';
    case SupportManage = 'support.manage';
    case SupportViewAll = 'support.view_all';
    case MacrosManage = 'macros.manage';
    case ReviewsView = 'reviews.view';
    case ReviewsModerate = 'reviews.moderate';
    case RiskView = 'risk.view';
    case RiskManage = 'risk.manage';
    case ContentManage = 'content.manage';
    case ContentPublish = 'content.publish';
    case TranslationsManage = 'translations.manage';
    case LeadsView = 'leads.view';
    case NotificationsManage = 'notifications.manage';
    case PushCampaignsManage = 'push_campaigns.manage';
    case ReportsView = 'reports.view';
    case ReportsExport = 'reports.export';
    case SystemHealth = 'system.health';
    case SystemFeatureFlags = 'system.feature_flags';
    case SystemAppVersions = 'system.app_versions';
    case SystemQueues = 'system.queues';
    case OtpChallengesView = 'otp_challenges.view';
    case DemoItemsView = 'demo_items.view';
    case DemoItemsManage = 'demo_items.manage';
    case DemoItemsExport = 'demo_items.export';
    // @admin-module:permissions

    public function group(): string
    {
        return explode('.', $this->value, 2)[0];
    }

    public function action(): string
    {
        return explode('.', $this->value, 2)[1];
    }

    public function label(): string
    {
        return __('enums.admin_permission.label', [
            'group' => __("enums.admin_permission_group.{$this->group()}"),
            'action' => __("enums.admin_permission_action.{$this->action()}"),
        ]);
    }

    /**
     * Cases grouped by their group key, for role editors.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->group()][] = $case;
        }

        return $grouped;
    }
}
