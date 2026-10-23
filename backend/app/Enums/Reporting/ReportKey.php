<?php

namespace App\Enums\Reporting;

use App\DTOs\Reporting\ReportColumn;
use App\Enums\Admin\AdminPermission;
use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * The reports of SRS 16.14 (ADR-178). Each report = a collapsed filters card, a chart, a Yajra table and a queued, audited CSV export.
 * Every report needs `reports.view` AND the permission of the data it reads (finance, support, safety...); PII columns need
 * `reports.export_pii`. `referral` is a documented placeholder until P5.7 (referrals) exists.
 */
enum ReportKey: string implements HasLabel
{
    use EnumHelpers;

    case BookingFunnel = 'booking-funnel';
    case SupplyDemand = 'supply-demand';
    case Fares = 'fares';
    case ProviderAcceptance = 'provider-acceptance';
    case Completion = 'completion';
    case Cancellation = 'cancellation';
    case CouponRoi = 'coupon-roi';
    case ProviderEarnings = 'provider-earnings';
    case Commissions = 'commissions';
    case PayoutAgeing = 'payout-ageing';
    case PaymentReconciliation = 'payment-reconciliation';
    case SupportSla = 'support-sla';
    case SafetyTrends = 'safety-trends';
    case DocumentExpiry = 'document-expiry';
    case Retention = 'retention';
    case Referral = 'referral';

    public function label(): string
    {
        return __("reporting.reports.{$this->value}.title");
    }

    public function description(): string
    {
        return __("reporting.reports.{$this->value}.description");
    }

    public function permission(): AdminPermission
    {
        return match ($this) {
            self::BookingFunnel, self::SupplyDemand, self::Completion, self::Cancellation => AdminPermission::BookingsView,
            self::Fares, self::ProviderAcceptance => AdminPermission::OffersView,
            self::CouponRoi => AdminPermission::CouponsView,
            self::ProviderEarnings, self::Commissions => AdminPermission::FinanceView,
            self::PayoutAgeing => AdminPermission::PayoutsView,
            self::PaymentReconciliation => AdminPermission::PaymentsView,
            self::SupportSla => AdminPermission::SupportView,
            self::SafetyTrends => AdminPermission::IncidentsView,
            self::DocumentExpiry => AdminPermission::DocumentsView,
            self::Retention => AdminPermission::CustomersView,
            self::Referral => AdminPermission::ReportsView,
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::BookingFunnel, self::SupplyDemand, self::Completion, self::Cancellation, self::Retention => 'demand',
            self::Fares, self::ProviderAcceptance => 'marketplace',
            self::CouponRoi, self::ProviderEarnings, self::Commissions, self::PayoutAgeing, self::PaymentReconciliation => 'finance',
            self::SupportSla, self::SafetyTrends, self::DocumentExpiry => 'service',
            self::Referral => 'growth',
        };
    }

    public function icon(): string
    {
        return match ($this->group()) {
            'finance' => 'banknotes',
            'service' => 'lifebuoy',
            'marketplace' => 'tag',
            'growth' => 'megaphone',
            default => 'chart',
        };
    }

    /**
     * The metrics table the report reads; null = read live from the operational / ledger tables.
     */
    public function metricSet(): ?MetricSet
    {
        return match ($this) {
            self::BookingFunnel, self::Fares, self::ProviderAcceptance, self::Completion, self::Cancellation => MetricSet::Bookings,
            self::Commissions => MetricSet::Finance,
            self::SupportSla, self::SafetyTrends => MetricSet::Operations,
            default => null,
        };
    }

    public function freshness(): DataFreshness
    {
        return $this->metricSet() === null ? DataFreshness::Live : DataFreshness::Aggregated;
    }

    public function isPlaceholder(): bool
    {
        return $this === self::Referral;
    }

    /**
     * Reports bucketed by the grouping filter (date / city / zone / category).
     */
    public function isGroupable(): bool
    {
        return in_array($this, [self::BookingFunnel, self::Fares, self::ProviderAcceptance, self::Completion, self::Cancellation, self::Commissions], true);
    }

    /**
     * Reports with a zone dimension in their data (the zone filter applies).
     */
    public function hasZones(): bool
    {
        return in_array($this, [self::BookingFunnel, self::Fares, self::ProviderAcceptance, self::Completion, self::Cancellation, self::SupplyDemand], true);
    }

    public function hasCities(): bool
    {
        return $this->metricSet() === MetricSet::Bookings || $this === self::Commissions || $this === self::SupplyDemand;
    }

    public function hasCategories(): bool
    {
        return $this->hasCities() || $this === self::CouponRoi;
    }

    public function usesDateRange(): bool
    {
        return ! in_array($this, [self::PayoutAgeing, self::DocumentExpiry, self::Referral], true);
    }

    public function hasPiiColumns(): bool
    {
        return array_any($this->columns(), static fn (ReportColumn $column): bool => $column->pii);
    }

    /**
     * @return list<ReportColumn>
     */
    public function columns(): array
    {
        $bucket = ReportColumn::text('bucket_label');
        $money = static fn (string $key): ReportColumn => ReportColumn::money($key);
        $int = static fn (string $key): ReportColumn => ReportColumn::integer($key);
        $pct = static fn (string $key): ReportColumn => ReportColumn::percent($key);

        return match ($this) {
            self::BookingFunnel => [$bucket, $int('requests'), $int('with_offers'), $int('selected'), $int('completed'), $pct('request_to_offer'), $pct('offer_to_selection'), $pct('selection_to_completion'), $pct('overall_conversion')],
            self::SupplyDemand => [ReportColumn::text('zone_label'), ReportColumn::text('category_label'), $int('hour_of_day'), $int('requests'), $int('with_offers'), $int('unmet'), $int('offering_providers'), $pct('fill_rate')],
            self::Fares => [$bucket, ReportColumn::text('currency'), $int('requests'), $money('avg_proposed'), $int('offers_submitted'), $money('avg_offered'), $int('selected'), $money('avg_agreed'), $pct('agreed_vs_proposed')],
            self::ProviderAcceptance => [$bucket, $int('recipients_notified'), $int('recipients_responded'), $pct('response_rate'), $int('offers_submitted'), $pct('offer_rate'), $int('selected'), $pct('selection_rate'), ReportColumn::seconds('avg_response_seconds')],
            self::Completion => [$bucket, $int('requests'), $int('selected'), $int('completed'), $pct('completion_rate'), $pct('completion_of_selected')],
            self::Cancellation => [$bucket, $int('requests'), $int('cancelled_customer'), $int('cancelled_provider'), $int('cancelled_operations'), $int('expired'), $int('no_show'), $pct('cancellation_rate'), $pct('no_show_rate')],
            self::CouponRoi => [ReportColumn::text('coupon_code'), ReportColumn::text('currency'), $int('redemptions'), $money('discount_minor'), $money('platform_minor'), $money('fare_minor'), new ReportColumn('fare_per_spend', ReportColumnType::Decimal)],
            self::ProviderEarnings => [ReportColumn::text('provider_ref'), ReportColumn::text('provider_name', pii: true), ReportColumn::text('currency'), $int('jobs'), $money('earnings_minor'), $money('payouts_paid_minor')],
            self::Commissions => [$bucket, ReportColumn::text('currency'), $money('collected_minor'), $money('commission_minor'), $pct('commission_rate'), $money('provider_earnings_minor'), $money('refunds_minor')],
            self::PayoutAgeing => [ReportColumn::text('status_label'), ReportColumn::text('age_bucket'), ReportColumn::text('currency'), $int('requests'), $money('amount_minor'), $int('oldest_days')],
            self::PaymentReconciliation => [ReportColumn::text('ran_at'), ReportColumn::text('status_label'), $int('payments_checked'), $int('transactions_checked'), $int('timed_out'), $int('findings_count')],
            self::SupportSla => [$bucket, $int('tickets_opened'), $int('tickets_resolved'), $int('tickets_sla_breached'), $pct('breach_rate'), ReportColumn::seconds('avg_first_response_seconds')],
            self::SafetyTrends => [$bucket, $int('incidents_opened'), $int('incidents_urgent'), $int('incidents_resolved'), $int('incidents_sla_breached')],
            self::DocumentExpiry => [ReportColumn::text('document_type'), $int('expired'), $int('expiring_7'), $int('expiring_30'), $int('expiring_60'), $int('total_active')],
            self::Retention => [ReportColumn::text('cohort'), $int('customers'), $int('m1'), $int('m2'), $int('m3'), $pct('m1_rate'), $pct('m2_rate'), $pct('m3_rate')],
            self::Referral => [],
        };
    }

    /**
     * Chart series: column key, unit. The x axis is `bucket_label` (or the first text column of live reports).
     *
     * @return list<array{0: string, 1: KpiUnit}>
     */
    public function chartSeries(): array
    {
        $c = KpiUnit::Count;

        return match ($this) {
            self::BookingFunnel => [['requests', $c], ['with_offers', $c], ['selected', $c], ['completed', $c]],
            self::SupplyDemand => [['requests', $c], ['unmet', $c]],
            self::Fares => [['avg_proposed', KpiUnit::Money], ['avg_offered', KpiUnit::Money], ['avg_agreed', KpiUnit::Money]],
            self::ProviderAcceptance => [['recipients_notified', $c], ['offers_submitted', $c], ['selected', $c]],
            self::Completion => [['requests', $c], ['completed', $c]],
            self::Cancellation => [['cancelled_customer', $c], ['cancelled_provider', $c], ['expired', $c], ['no_show', $c]],
            self::CouponRoi => [['discount_minor', KpiUnit::Money], ['platform_minor', KpiUnit::Money]],
            self::ProviderEarnings => [['earnings_minor', KpiUnit::Money], ['payouts_paid_minor', KpiUnit::Money]],
            self::Commissions => [['commission_minor', KpiUnit::Money], ['provider_earnings_minor', KpiUnit::Money], ['refunds_minor', KpiUnit::Money]],
            self::PayoutAgeing => [['amount_minor', KpiUnit::Money]],
            self::PaymentReconciliation => [['payments_paid', $c], ['payments_failed', $c]],
            self::SupportSla => [['tickets_opened', $c], ['tickets_resolved', $c], ['tickets_sla_breached', $c]],
            self::SafetyTrends => [['incidents_opened', $c], ['incidents_urgent', $c], ['incidents_sla_breached', $c]],
            self::DocumentExpiry => [['expired', $c], ['expiring_7', $c], ['expiring_30', $c], ['expiring_60', $c]],
            self::Retention => [['customers', $c], ['m1', $c], ['m2', $c], ['m3', $c]],
            self::Referral => [],
        };
    }

    /**
     * Whether the chart shows a time series (line) rather than categories (bars).
     */
    public function isTimeSeries(ReportGrouping $grouping): bool
    {
        return match ($this) {
            self::SupportSla, self::SafetyTrends, self::PaymentReconciliation => true,
            self::BookingFunnel, self::Fares, self::ProviderAcceptance, self::Completion, self::Cancellation, self::Commissions => $grouping === ReportGrouping::Date,
            default => false,
        };
    }
}
