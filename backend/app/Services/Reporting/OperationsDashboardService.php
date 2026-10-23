<?php

namespace App\Services\Reporting;

use App\DTOs\Reporting\ChartData;
use App\DTOs\Reporting\ChartPanel;
use App\DTOs\Reporting\KpiTile;
use App\DTOs\Reporting\OperationsDashboard;
use App\DTOs\Reporting\ReportFilterData;
use App\DTOs\Reporting\ReportingRange;
use App\Enums\Booking\BookingStatus;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payout\PayoutStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Reporting\KpiKey;
use App\Enums\Reporting\KpiUnit;
use App\Enums\Reporting\MetricSet;
use App\Enums\Safety\IncidentStatus;
use App\Enums\Support\TicketStatus;
use App\Models\Admin;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\Reporting\ReportingRepositoryInterface;
use App\Repositories\Contracts\Reviews\RatingModerationRepositoryInterface;
use App\Repositories\Contracts\Risk\RiskSignalRepositoryInterface;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Support\Reporting\ReportValueFormatter;
use BackedEnum;
use Carbon\CarbonInterface;

/**
 * Builds the operations dashboard (SRS 16.1, ADR-178). Permission-aware per widget: a KPI or chart is built only when the admin holds
 * the permission of its drill-down listing, so a role never sees a number it cannot open. Aggregated KPIs read the daily metrics for the
 * market-day range (with a comparison to the previous period); operational KPIs are live counters, reusing the cheap counters of the
 * incidents, reviews and risk modules. Financial figures come from payment records and ledger lines (the finance metrics), in ONE currency.
 */
final class OperationsDashboardService
{
    public function __construct(
        private readonly ReportingRepositoryInterface $reporting,
        private readonly SafetyIncidentRepositoryInterface $incidents,
        private readonly RatingModerationRepositoryInterface $moderation,
        private readonly RiskSignalRepositoryInterface $risk,
        private readonly ReportValueFormatter $format,
    ) {}

    public function build(Admin $admin, ReportFilterData $filters): OperationsDashboard
    {
        $currencies = $this->reporting->currencies($filters);
        $currency = $filters->currency ?? ($currencies[0] ?? (string) config('admin.default_currency', 'EGP'));
        $allowed = static fn (KpiKey $key): bool => $admin->can($key->permission()->value);

        $needs = static fn (MetricSet $set): bool => array_filter(KpiKey::cases(), static fn (KpiKey $key): bool => $key->metricSet() === $set && $allowed($key)) !== [];
        $totals = $needs(MetricSet::Bookings) ? $this->reporting->bookingTotals($filters, $filters->range) : [];
        $previous = $needs(MetricSet::Bookings) ? $this->reporting->bookingTotals($filters, $filters->range->previous()) : [];
        $finance = $needs(MetricSet::Finance) ? $this->reporting->financeTotals($filters, $filters->range, $currency) : [];
        $financePrevious = $needs(MetricSet::Finance) ? $this->reporting->financeTotals($filters, $filters->range->previous(), $currency) : [];
        $live = $this->reporting->liveCounters();
        $asOf = [];

        foreach (MetricSet::cases() as $set) {
            $asOf[$set->value] = $this->reporting->lastAggregatedAt($set);
        }

        $tiles = [];

        foreach (KpiKey::cases() as $key) {
            if (! $allowed($key)) {
                continue;
            }

            $tiles[] = $this->tile($key, $filters, $totals, $previous, $finance, $financePrevious, $live, $currency, $asOf);
        }

        return new OperationsDashboard($filters, $currency, $currencies, $tiles, $this->charts($admin, $filters, $currency), $asOf);
    }

    /**
     * @param array<string, int> $totals
     * @param array<string, int> $previous
     * @param array<string, int> $finance
     * @param array<string, int> $financePrevious
     * @param array<string, mixed> $live
     * @param array<string, CarbonInterface|null> $asOf
     */
    private function tile(KpiKey $key, ReportFilterData $filters, array $totals, array $previous, array $finance, array $financePrevious, array $live, string $currency, array $asOf): KpiTile
    {
        $ratio = static fn (array $data, string $numerator, string $denominator): ?float => ($data[$denominator] ?? 0) > 0 ? round(100 * ($data[$numerator] ?? 0) / $data[$denominator], 1) : null;
        $cancelled = static fn (array $data): int => ($data['cancelled_customer'] ?? 0) + ($data['cancelled_provider'] ?? 0) + ($data['cancelled_operations'] ?? 0);
        $range = $filters->range;
        $hint = null;
        $comparison = null;

        switch ($key) {
            case KpiKey::Requests:
                $value = $totals['requests'] ?? 0;
                $comparison = [$value, $previous['requests'] ?? 0, false];
                break;
            case KpiKey::RequestToOffer:
                $value = $ratio($totals, 'with_offers', 'requests');
                $comparison = [$value, $ratio($previous, 'with_offers', 'requests'), true];
                break;
            case KpiKey::OfferToSelection:
                $value = $ratio($totals, 'selected', 'with_offers');
                $comparison = [$value, $ratio($previous, 'selected', 'with_offers'), true];
                break;
            case KpiKey::CompletionRate:
                $value = $ratio($totals, 'completed', 'requests');
                $comparison = [$value, $ratio($previous, 'completed', 'requests'), true];
                break;
            case KpiKey::CancellationRate:
                $value = ($totals['requests'] ?? 0) > 0 ? round(100 * $cancelled($totals) / $totals['requests'], 1) : null;
                $comparison = [$value, ($previous['requests'] ?? 0) > 0 ? round(100 * $cancelled($previous) / $previous['requests'], 1) : null, true];
                break;
            case KpiKey::NoShowRate:
                $value = $ratio($totals, 'no_show', 'requests');
                $comparison = [$value, $ratio($previous, 'no_show', 'requests'), true];
                break;
            case KpiKey::AvgOfferResponse:
                $value = ($totals['response_seconds_count'] ?? 0) > 0 ? round($totals['response_seconds_sum'] / $totals['response_seconds_count']) : null;
                break;
            case KpiKey::EstimatedGmv:
                $value = $totals['estimated_gmv_minor'] ?? 0;
                $comparison = [$value, $previous['estimated_gmv_minor'] ?? 0, false];
                $hint = $currency;
                break;
            case KpiKey::RealizedGmv:
                $value = $finance['payments_paid_minor'] ?? 0;
                $comparison = [$value, $financePrevious['payments_paid_minor'] ?? 0, false];
                $hint = $currency;
                break;
            case KpiKey::CommissionRevenue:
                $value = $finance['commission_minor'] ?? 0;
                $comparison = [$value, $financePrevious['commission_minor'] ?? 0, false];
                $hint = $currency;
                break;
            case KpiKey::PaymentSuccessRate:
                $attempts = ($finance['payments_paid'] ?? 0) + ($finance['payments_failed'] ?? 0);
                $value = $attempts > 0 ? round(100 * $finance['payments_paid'] / $attempts, 1) : null;
                $previousAttempts = ($financePrevious['payments_paid'] ?? 0) + ($financePrevious['payments_failed'] ?? 0);
                $comparison = [$value, $previousAttempts > 0 ? round(100 * $financePrevious['payments_paid'] / $previousAttempts, 1) : null, true];
                break;
            case KpiKey::PaymentsFailed:
                $value = $finance['payments_failed'] ?? 0;
                $comparison = [$value, $financePrevious['payments_failed'] ?? 0, false];
                break;
            case KpiKey::ActiveDrivers:
                $value = $live['active_drivers'];
                break;
            case KpiKey::WaitingRequests:
                $value = $live['waiting_requests'];
                break;
            case KpiKey::OpenTickets:
                $value = $live['open_tickets'];
                break;
            case KpiKey::PendingVerifications:
                $value = $live['pending_verifications'];
                break;
            case KpiKey::PendingPayouts:
                $value = $live['pending_payouts'];
                $amount = $live['pending_payouts_minor'][$currency] ?? null;
                $hint = $amount === null ? null : $this->format->moneyValue((int) $amount, $currency);
                break;
            case KpiKey::OpenIncidents:
                $value = $this->incidents->countActive();
                break;
            case KpiKey::OpenReviewReports:
                $value = $this->moderation->countOpenReports();
                break;
            case KpiKey::OpenAppeals:
                $value = $this->moderation->countOpenAppeals();
                break;
            case KpiKey::ActiveRiskSignals:
                $value = $this->risk->countActive();
                break;
        }

        $unit = $key->unit();
        [$change, $direction] = $comparison === null ? [null, null] : $this->change($comparison[0], $comparison[1], $comparison[2]);
        $set = $key->metricSet();

        return new KpiTile(
            key: $key,
            value: $this->format->kpi($unit, $value, $currency),
            hint: $hint,
            href: $this->drillDown($key, $range, $filters),
            definition: $key->definition(),
            freshness: $key->freshness(),
            asOf: $set === null ? null : ($asOf[$set->value] ?? null),
            change: $change,
            changeDirection: $direction,
        );
    }

    /**
     * Change against the previous period: percent for counts and money, points for rates. Null when there is nothing to compare.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function change(int|float|null $current, int|float|null $previous, bool $points): array
    {
        if ($current === null || $previous === null || ($previous === 0 && ! $points)) {
            return [null, null];
        }

        $delta = $points ? $current - $previous : 100 * ($current - $previous) / $previous;
        $direction = abs($delta) < 0.05 ? 'flat' : ($delta > 0 ? 'up' : 'down');
        $number = $this->format->number(abs($delta), 1);

        return [($delta > 0 ? '+' : ($delta < 0 ? '−' : '')).$number.($points ? ' '.__('reporting.units.points') : '%'), $direction];
    }

    /**
     * Link of the listing behind a KPI with its filters pre-applied (`?filters[...]`). Every key used here exists in the target filter DTO.
     */
    private function drillDown(KpiKey $key, ReportingRange $range, ReportFilterData $filters): string
    {
        $created = ['created_at' => ['from' => $range->from->toDateString(), 'to' => $range->to->toDateString()]];
        $scope = array_filter(['city_ids' => $filters->cityIds, 'zone_ids' => $filters->zoneIds, 'service_category_ids' => $filters->categoryIds]);
        $statuses = static fn (array $cases): array => array_map(static fn (BackedEnum $case): string => (string) $case->value, $cases);
        $url = static fn (string $route, array $query): string => route($route, ['filters' => $query]);

        return match ($key) {
            KpiKey::Requests, KpiKey::RequestToOffer, KpiKey::OfferToSelection, KpiKey::EstimatedGmv => $url('admin.bookings.index', $created + $scope),
            KpiKey::CompletionRate => $url('admin.bookings.index', $created + $scope + ['statuses' => $statuses([BookingStatus::Completed])]),
            KpiKey::CancellationRate, KpiKey::NoShowRate => $url('admin.bookings.index', $created + $scope + ['statuses' => $statuses([BookingStatus::CancelledByCustomer, BookingStatus::CancelledByProvider, BookingStatus::CancelledByOperations])]),
            KpiKey::AvgOfferResponse => $url('admin.offers.index', $created),
            KpiKey::ActiveDrivers => $url('admin.providers.index', ['online' => '1']),
            KpiKey::WaitingRequests => $url('admin.bookings.index', ['statuses' => $statuses([BookingStatus::SearchingOffers, BookingStatus::OffersReceived])]),
            KpiKey::RealizedGmv, KpiKey::PaymentSuccessRate => $url('admin.payments.index', $created + ['statuses' => $statuses([PaymentStatus::Paid])]),
            KpiKey::CommissionRevenue => $url('admin.journal-entries.index', ['posted_at' => $created['created_at']]),
            KpiKey::PaymentsFailed => $url('admin.payments.index', $created + ['statuses' => $statuses([PaymentStatus::Failed])]),
            KpiKey::OpenIncidents => $url('admin.safety-incidents.index', ['statuses' => $statuses([IncidentStatus::New, IncidentStatus::Acknowledged, IncidentStatus::Investigating, IncidentStatus::Escalated])]),
            KpiKey::OpenTickets => $url('admin.support-tickets.index', ['statuses' => $statuses(TicketStatus::activeCases())]),
            KpiKey::PendingVerifications => $url('admin.verification.index', ['statuses' => $statuses([ProviderStatus::UnderReview])]),
            KpiKey::PendingPayouts => $url('admin.payout-requests.index', ['statuses' => $statuses(PayoutStatus::awaitingDecision())]),
            KpiKey::OpenReviewReports => $url('admin.reviews.index', ['reported_only' => '1']),
            KpiKey::OpenAppeals => route('admin.reviews.index'),
            KpiKey::ActiveRiskSignals => $url('admin.risk-signals.index', []),
        };
    }

    /**
     * @return list<ChartPanel>
     */
    private function charts(Admin $admin, ReportFilterData $filters, string $currency): array
    {
        $panels = [];
        $dates = $filters->range->dates();
        $range = $filters->range;
        $created = ['created_at' => ['from' => $range->from->toDateString(), 'to' => $range->to->toDateString()]];

        if ($admin->can(KpiKey::Requests->permission()->value)) {
            $series = $this->reporting->bookingSeries($filters);
            $pick = static fn (string $column): array => array_map(static fn (string $date): int => $series[$date][$column] ?? 0, $dates);
            $panels[] = new ChartPanel(
                'bookings-trend',
                (string) __('reporting.dashboard.charts.bookings_trend'),
                new ChartData('line', $dates, [
                    ['key' => 'requests', 'label' => (string) __('reporting.columns.requests'), 'data' => $pick('requests'), 'slot' => 1],
                    ['key' => 'completed', 'label' => (string) __('reporting.columns.completed'), 'data' => $pick('completed'), 'slot' => 3],
                    ['key' => 'cancelled', 'label' => (string) __('reporting.dashboard.cancelled'), 'data' => $pick('cancelled'), 'slot' => 2],
                ], labelKind: 'date'),
                route('admin.reports.show', ['report' => 'booking-funnel']),
                KpiKey::Requests,
            );

            $statusCounts = $this->reporting->bookingsByStatus($filters);
            $statusLabels = [];
            $statusData = [];

            foreach (BookingStatus::cases() as $status) {
                if (($statusCounts[$status->value] ?? 0) > 0) {
                    $statusLabels[] = $status->label();
                    $statusData[] = $statusCounts[$status->value];
                }
            }

            $panels[] = new ChartPanel(
                'bookings-status',
                (string) __('reporting.dashboard.charts.bookings_status'),
                new ChartData('donut', $statusLabels, [['key' => 'status', 'label' => (string) __('reporting.dashboard.charts.bookings_status'), 'data' => $statusData, 'slot' => 1]]),
                route('admin.bookings.index', ['filters' => $created]),
                KpiKey::Requests,
                \App\Enums\Reporting\DataFreshness::Live,
            );

            $byCategory = $this->reporting->requestsByCategory($filters);
            $names = ServiceCategory::query()->whereIn('id', array_keys($byCategory))->get()->mapWithKeys(static fn (ServiceCategory $category): array => [$category->id => (string) $category->getTranslation('name', app()->getLocale(), true)]);
            $top = array_slice($byCategory, 0, 8, true);

            $panels[] = new ChartPanel(
                'requests-category',
                (string) __('reporting.dashboard.charts.requests_category'),
                new ChartData('bar', array_map(static fn (int $id): string => $names[$id] ?? '—', array_keys($top)), [['key' => 'requests', 'label' => (string) __('reporting.columns.requests'), 'data' => array_values($top), 'slot' => 1]]),
                route('admin.reports.show', ['report' => 'booking-funnel', 'filters' => ['group_by' => 'category']]),
                KpiKey::Requests,
            );
        }

        if ($admin->can(KpiKey::CommissionRevenue->permission()->value)) {
            $series = $this->reporting->financeSeries($filters, $currency);
            $pick = static fn (string $column): array => array_map(static fn (string $date): int => $series[$date][$column] ?? 0, $dates);
            $panels[] = new ChartPanel(
                'revenue-trend',
                (string) __('reporting.dashboard.charts.revenue_trend'),
                new ChartData('line', $dates, [
                    ['key' => 'payments_paid_minor', 'label' => (string) __('reporting.kpi.realized_gmv.label'), 'data' => $pick('payments_paid_minor'), 'slot' => 1],
                    ['key' => 'commission_minor', 'label' => (string) __('reporting.kpi.commission_revenue.label'), 'data' => $pick('commission_minor'), 'slot' => 3],
                ], KpiUnit::Money, $currency, 'date', (string) __('reporting.chart.currency_note', ['currency' => $currency])),
                route('admin.reports.show', ['report' => 'commissions']),
                KpiKey::CommissionRevenue,
            );
        }

        if ($admin->can(KpiKey::PaymentsFailed->permission()->value)) {
            $series = $this->reporting->financeSeries($filters, $currency);
            $pick = static fn (string $column): array => array_map(static fn (string $date): int => $series[$date][$column] ?? 0, $dates);
            $panels[] = new ChartPanel(
                'payments-trend',
                (string) __('reporting.dashboard.charts.payments_trend'),
                new ChartData('bar', $dates, [
                    ['key' => 'payments_paid', 'label' => (string) __('reporting.columns.payments_paid'), 'data' => $pick('payments_paid'), 'slot' => 3],
                    ['key' => 'payments_failed', 'label' => (string) __('reporting.columns.payments_failed'), 'data' => $pick('payments_failed'), 'slot' => 2],
                ], labelKind: 'date'),
                route('admin.reports.show', ['report' => 'payment-reconciliation']),
                KpiKey::PaymentSuccessRate,
            );
        }

        return $panels;
    }
}
