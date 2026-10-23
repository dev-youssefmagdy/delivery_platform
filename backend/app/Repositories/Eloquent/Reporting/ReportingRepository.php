<?php

namespace App\Repositories\Eloquent\Reporting;

use App\DTOs\Reporting\ReportFilterData;
use App\DTOs\Reporting\ReportingRange;
use App\Enums\Booking\BookingStatus;
use App\Enums\Payout\PayoutStatus;
use App\Enums\Provider\ProviderStatus;
use App\Enums\Reporting\MetricSet;
use App\Enums\Support\TicketStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\MetricAggregationState;
use App\Models\MetricDailyBooking;
use App\Models\MetricDailyFinance;
use App\Models\PayoutRequest;
use App\Models\ProviderProfile;
use App\Models\SupportTicket;
use App\Repositories\Contracts\Reporting\ReportingRepositoryInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Dashboard reads (ADR-178): sums over `metrics_daily_*` and cheap live counters. Financial figures come from the finance metrics, which
 * are built from payment records and ledger lines only; nothing reads a cached display total.
 */
final class ReportingRepository implements ReportingRepositoryInterface
{
    private const BOOKING_SUMS = [
        'requests', 'with_offers', 'selected', 'completed', 'cancelled_customer', 'cancelled_provider', 'cancelled_operations', 'expired', 'no_show',
        'estimated_gmv_minor', 'offers_submitted', 'recipients_notified', 'recipients_responded', 'response_seconds_sum', 'response_seconds_count',
    ];

    private const FINANCE_SUMS = ['payments_paid', 'payments_paid_minor', 'payments_failed', 'payments_failed_minor', 'refunds_minor', 'commission_minor', 'provider_earnings_minor'];

    public function bookingTotals(ReportFilterData $filters, ReportingRange $range): array
    {
        $row = $this->bookings($filters, $range)
            ->selectRaw(implode(', ', array_map(static fn (string $column): string => "coalesce(sum({$column}), 0) as {$column}", self::BOOKING_SUMS)))
            ->toBase()->first();

        return array_map('intval', (array) $row);
    }

    public function financeTotals(ReportFilterData $filters, ReportingRange $range, string $currency): array
    {
        $row = $this->finance($filters, $range)
            ->where('currency', $currency)
            ->selectRaw(implode(', ', array_map(static fn (string $column): string => "coalesce(sum({$column}), 0) as {$column}", self::FINANCE_SUMS)))
            ->toBase()->first();

        return array_map('intval', (array) $row);
    }

    public function bookingSeries(ReportFilterData $filters): array
    {
        return $this->bookings($filters, $filters->range)
            ->selectRaw('metric_date, sum(requests) as requests, sum(completed) as completed, sum(cancelled_customer + cancelled_provider + cancelled_operations) as cancelled, sum(expired) as expired')
            ->groupBy('metric_date')->orderBy('metric_date')
            ->toBase()->get()
            ->mapWithKeys(static fn (object $row): array => [substr((string) $row->metric_date, 0, 10) => ['requests' => (int) $row->requests, 'completed' => (int) $row->completed, 'cancelled' => (int) $row->cancelled, 'expired' => (int) $row->expired]])
            ->all();
    }

    public function financeSeries(ReportFilterData $filters, string $currency): array
    {
        return $this->finance($filters, $filters->range)
            ->where('currency', $currency)
            ->selectRaw('metric_date, sum(payments_paid) as payments_paid, sum(payments_paid_minor) as payments_paid_minor, sum(payments_failed) as payments_failed, sum(commission_minor) as commission_minor')
            ->groupBy('metric_date')->orderBy('metric_date')
            ->toBase()->get()
            ->mapWithKeys(static fn (object $row): array => [substr((string) $row->metric_date, 0, 10) => [
                'payments_paid' => (int) $row->payments_paid, 'payments_paid_minor' => (int) $row->payments_paid_minor,
                'payments_failed' => (int) $row->payments_failed, 'commission_minor' => (int) $row->commission_minor,
            ]])
            ->all();
    }

    public function requestsByCategory(ReportFilterData $filters): array
    {
        return $this->bookings($filters, $filters->range)
            ->selectRaw('service_category_id, sum(requests) as requests')
            ->groupBy('service_category_id')->orderByDesc('requests')
            ->toBase()->get()
            ->mapWithKeys(static fn (object $row): array => [(int) $row->service_category_id => (int) $row->requests])
            ->all();
    }

    public function bookingsByStatus(ReportFilterData $filters): array
    {
        $timezone = $filters->range->timezone ?? (string) config('admin.display_timezone', 'UTC');
        $start = CarbonImmutable::parse($filters->range->from->toDateString(), $timezone)->startOfDay()->utc();
        $end = CarbonImmutable::parse($filters->range->to->toDateString(), $timezone)->addDay()->startOfDay()->utc();

        return Booking::query()
            ->where('created_at', '>=', $start)->where('created_at', '<', $end)
            ->where('status', '!=', BookingStatus::Draft->value)
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('city_id', $filters->cityIds))
            ->when($filters->zoneIds !== [], static fn (Builder $query) => $query->whereIn('service_zone_id', $filters->zoneIds))
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->categoryIds))
            ->when($filters->range->timezone !== null, static fn (Builder $query) => $query->whereIn('city_id', City::query()->where('timezone', $filters->range->timezone)->select('id')))
            ->selectRaw('status, count(*) as total')->groupBy('status')
            ->toBase()->get()
            ->mapWithKeys(static fn (object $row): array => [(string) $row->status => (int) $row->total])
            ->all();
    }

    public function currencies(ReportFilterData $filters): array
    {
        return $this->finance($filters, $filters->range)
            ->selectRaw('currency, sum(payments_paid + payments_failed) as weight')
            ->groupBy('currency')->orderByDesc('weight')
            ->toBase()->pluck('currency')->map(static fn (mixed $code): string => (string) $code)->all();
    }

    public function liveCounters(): array
    {
        $payouts = PayoutRequest::query()->whereIn('status', array_map(static fn (PayoutStatus $status): string => $status->value, PayoutStatus::awaitingDecision()));

        return [
            'active_drivers' => ProviderProfile::query()->where('is_online', true)->count(),
            'waiting_requests' => Booking::query()->whereIn('status', [BookingStatus::SearchingOffers->value, BookingStatus::OffersReceived->value])->count(),
            'open_tickets' => SupportTicket::query()->whereIn('status', array_map(static fn (TicketStatus $status): string => $status->value, TicketStatus::activeCases()))->count(),
            'pending_verifications' => ProviderProfile::query()->where('status', ProviderStatus::UnderReview->value)->count(),
            'pending_payouts' => (clone $payouts)->count(),
            'pending_payouts_minor' => (clone $payouts)->selectRaw('currency, sum(amount_minor) as total')->groupBy('currency')->toBase()->get()
                ->mapWithKeys(static fn (object $row): array => [(string) $row->currency => (int) $row->total])->all(),
        ];
    }

    public function lastAggregatedAt(MetricSet $set): ?CarbonInterface
    {
        return MetricAggregationState::query()->where('metric_set', $set->value)->first()?->last_aggregated_at;
    }

    public function marketTimezones(): array
    {
        return City::query()->distinct()->orderBy('timezone')->pluck('timezone')->map(static fn (mixed $tz): string => (string) $tz)->all();
    }

    /**
     * @return Builder<MetricDailyBooking>
     */
    private function bookings(ReportFilterData $filters, ReportingRange $range): Builder
    {
        return MetricDailyBooking::query()
            ->whereBetween('metric_date', [$range->from->toDateString(), $range->to->toDateString()])
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('city_id', $filters->cityIds))
            ->when($filters->zoneIds !== [], static fn (Builder $query) => $query->whereIn('service_zone_id', $filters->zoneIds))
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->categoryIds))
            ->when($range->timezone !== null, static fn (Builder $query) => $query->whereIn('city_id', City::query()->where('timezone', $range->timezone)->select('id')));
    }

    /**
     * @return Builder<MetricDailyFinance>
     */
    private function finance(ReportFilterData $filters, ReportingRange $range): Builder
    {
        return MetricDailyFinance::query()
            ->whereBetween('metric_date', [$range->from->toDateString(), $range->to->toDateString()])
            ->when($filters->cityIds !== [], static fn (Builder $query) => $query->whereIn('city_id', $filters->cityIds))
            ->when($filters->categoryIds !== [], static fn (Builder $query) => $query->whereIn('service_category_id', $filters->categoryIds))
            ->when($range->timezone !== null, static fn (Builder $query) => $query->whereIn('city_id', City::query()->where('timezone', $range->timezone)->select('id')));
    }
}
