<?php

namespace App\Enums\Reporting;

use App\Enums\Admin\AdminPermission;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The KPIs of the operations dashboard (SRS 16.1, ADR-178). Every KPI has a definition (numerator, denominator, timezone, freshness)
 * in `lang/*\/reporting.php` under `kpi.<key>`, shown through the info tooltip, and a permission: a widget is rendered only for an admin
 * who may see its drill-down listing, and the dashboard never shows personal data.
 */
enum KpiKey: string implements HasLabel
{
    use EnumHelpers;

    case Requests = 'requests';
    case RequestToOffer = 'request_to_offer';
    case OfferToSelection = 'offer_to_selection';
    case CompletionRate = 'completion_rate';
    case CancellationRate = 'cancellation_rate';
    case NoShowRate = 'no_show_rate';
    case AvgOfferResponse = 'avg_offer_response';
    case ActiveDrivers = 'active_drivers';
    case WaitingRequests = 'waiting_requests';
    case EstimatedGmv = 'estimated_gmv';
    case RealizedGmv = 'realized_gmv';
    case CommissionRevenue = 'commission_revenue';
    case PaymentSuccessRate = 'payment_success_rate';
    case PaymentsFailed = 'payments_failed';
    case OpenIncidents = 'open_incidents';
    case OpenTickets = 'open_tickets';
    case PendingVerifications = 'pending_verifications';
    case PendingPayouts = 'pending_payouts';
    case OpenReviewReports = 'open_review_reports';
    case OpenAppeals = 'open_appeals';
    case ActiveRiskSignals = 'active_risk_signals';

    public function label(): string
    {
        return __("reporting.kpi.{$this->value}.label");
    }

    public function permission(): AdminPermission
    {
        return match ($this) {
            self::Requests, self::RequestToOffer, self::OfferToSelection, self::CompletionRate, self::CancellationRate, self::NoShowRate,
            self::AvgOfferResponse, self::WaitingRequests => AdminPermission::BookingsView,
            self::ActiveDrivers, self::PendingVerifications => AdminPermission::DriversView,
            self::EstimatedGmv, self::RealizedGmv, self::CommissionRevenue => AdminPermission::FinanceView,
            self::PaymentSuccessRate, self::PaymentsFailed => AdminPermission::PaymentsView,
            self::OpenIncidents => AdminPermission::IncidentsView,
            self::OpenTickets => AdminPermission::SupportView,
            self::PendingPayouts => AdminPermission::PayoutsView,
            self::OpenReviewReports, self::OpenAppeals => AdminPermission::ReviewsView,
            self::ActiveRiskSignals => AdminPermission::RiskView,
        };
    }

    public function unit(): KpiUnit
    {
        return match ($this) {
            self::RequestToOffer, self::OfferToSelection, self::CompletionRate, self::CancellationRate, self::NoShowRate, self::PaymentSuccessRate => KpiUnit::Percent,
            self::EstimatedGmv, self::RealizedGmv, self::CommissionRevenue => KpiUnit::Money,
            self::AvgOfferResponse => KpiUnit::Seconds,
            default => KpiUnit::Count,
        };
    }

    public function freshness(): DataFreshness
    {
        return match ($this) {
            self::ActiveDrivers, self::WaitingRequests, self::OpenIncidents, self::OpenTickets, self::PendingVerifications, self::PendingPayouts,
            self::OpenReviewReports, self::OpenAppeals, self::ActiveRiskSignals => DataFreshness::Live,
            default => DataFreshness::Aggregated,
        };
    }

    public function metricSet(): ?MetricSet
    {
        return match ($this) {
            self::Requests, self::RequestToOffer, self::OfferToSelection, self::CompletionRate, self::CancellationRate, self::NoShowRate,
            self::AvgOfferResponse, self::EstimatedGmv => MetricSet::Bookings,
            self::RealizedGmv, self::CommissionRevenue, self::PaymentSuccessRate, self::PaymentsFailed => MetricSet::Finance,
            default => null,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Requests, self::WaitingRequests => 'clipboard',
            self::RequestToOffer, self::OfferToSelection => 'tag',
            self::CompletionRate => 'check-circle',
            self::CancellationRate, self::NoShowRate => 'x-circle',
            self::AvgOfferResponse => 'clock',
            self::ActiveDrivers, self::PendingVerifications => 'users',
            self::EstimatedGmv, self::RealizedGmv, self::CommissionRevenue, self::PendingPayouts => 'banknotes',
            self::PaymentSuccessRate, self::PaymentsFailed => 'banknotes',
            self::OpenIncidents => 'shield',
            self::OpenTickets => 'lifebuoy',
            self::OpenReviewReports, self::OpenAppeals => 'star',
            self::ActiveRiskSignals => 'exclamation',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::CompletionRate, self::RealizedGmv, self::CommissionRevenue, self::PaymentSuccessRate => 'success',
            self::CancellationRate, self::NoShowRate, self::PaymentsFailed, self::OpenIncidents, self::ActiveRiskSignals => 'danger',
            self::WaitingRequests, self::PendingVerifications, self::PendingPayouts, self::OpenTickets, self::OpenReviewReports, self::OpenAppeals => 'warning',
            default => 'info',
        };
    }

    /**
     * @return array{numerator: string, denominator: string, timezone: string}
     */
    public function definition(): array
    {
        return [
            'numerator' => (string) __("reporting.kpi.{$this->value}.numerator"),
            'denominator' => (string) __("reporting.kpi.{$this->value}.denominator"),
            'timezone' => (string) __($this->freshness() === DataFreshness::Live ? 'reporting.timezone.live' : 'reporting.timezone.market'),
        ];
    }
}
