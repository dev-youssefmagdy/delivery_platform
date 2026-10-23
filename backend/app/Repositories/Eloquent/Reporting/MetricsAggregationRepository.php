<?php

namespace App\Repositories\Eloquent\Reporting;

use App\Enums\Booking\BookingStatus;
use App\Enums\Booking\CancellationReason;
use App\Enums\Booking\OfferKind;
use App\Enums\Coupon\CouponRedemptionStatus;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Payments\PaymentKind;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\RefundStatus;
use App\Enums\Reporting\MetricSet;
use App\Enums\Safety\IncidentSeverity;
use App\Models\City;
use App\Models\MetricAggregationState;
use App\Models\MetricDailyBooking;
use App\Models\MetricDailyFinance;
use App\Models\MetricDailyOperation;
use App\Repositories\Contracts\Reporting\MetricsAggregationRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Query-builder reads of the source tables and replace-writes of the metrics tables (ADR-178). The reads are plain aggregates over
 * indexed time columns of one city and one market day; nothing here mutates a source table.
 */
final class MetricsAggregationRepository implements MetricsAggregationRepositoryInterface
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function cityTimezones(): array
    {
        return City::query()->orderBy('id')->pluck('timezone', 'id')->map(static fn (mixed $tz): string => (string) $tz)->all();
    }

    public function bookingFacts(int $cityId, CarbonInterface $startUtc, CarbonInterface $endUtc): array
    {
        $cancelled = static fn (BookingStatus $status): string => "sum(b.status = '{$status->value}')";
        $noShow = "'".implode("','", [CancellationReason::DriverNoShow->value, CancellationReason::CustomerNoShow->value, CancellationReason::CustomerUnreachable->value])."'";
        $dead = "'".implode("','", [BookingStatus::CancelledByCustomer->value, BookingStatus::CancelledByProvider->value, BookingStatus::CancelledByOperations->value, BookingStatus::ExpiredNoProvider->value])."'";

        $requests = DB::table('bookings as b')
            ->selectRaw("coalesce(b.service_zone_id, 0) as zone_id, b.service_category_id as category_id, b.currency,
                count(*) as requests,
                sum(exists (select 1 from booking_offers o where o.booking_id = b.id)) as with_offers,
                sum(b.provider_selected_at is not null) as selected,
                sum(b.completed_at is not null) as completed,
                {$cancelled(BookingStatus::CancelledByCustomer)} as cancelled_customer,
                {$cancelled(BookingStatus::CancelledByProvider)} as cancelled_provider,
                {$cancelled(BookingStatus::CancelledByOperations)} as cancelled_operations,
                {$cancelled(BookingStatus::ExpiredNoProvider)} as expired,
                sum(b.cancellation_reason in ({$noShow})) as no_show,
                sum(b.proposed_fare_minor) as proposed_fare_sum_minor,
                sum(case when b.provider_selected_at is not null then coalesce(b.agreed_fare_minor, 0) else 0 end) as agreed_fare_sum_minor,
                sum(case when b.status not in ({$dead}) then coalesce(b.agreed_fare_minor, b.proposed_fare_minor) else 0 end) as estimated_gmv_minor")
            ->where('b.city_id', $cityId)
            ->where('b.created_at', '>=', $startUtc)
            ->where('b.created_at', '<', $endUtc)
            ->where('b.status', '!=', BookingStatus::Draft->value)
            ->groupByRaw('coalesce(b.service_zone_id, 0), b.service_category_id, b.currency')
            ->get();

        $offers = DB::table('booking_offers as o')
            ->join('bookings as b', 'b.id', '=', 'o.booking_id')
            ->leftJoin('booking_offer_versions as v', 'v.id', '=', 'o.current_version_id')
            ->selectRaw('coalesce(b.service_zone_id, 0) as zone_id, b.service_category_id as category_id, b.currency, count(*) as offers_submitted, sum(o.kind = ?) as offers_countered, coalesce(sum(v.amount_minor), 0) as offered_fare_sum_minor', [OfferKind::CounterOffer->value])
            ->where('b.city_id', $cityId)
            ->where('b.created_at', '>=', $startUtc)
            ->where('b.created_at', '<', $endUtc)
            ->where('b.status', '!=', BookingStatus::Draft->value)
            ->groupByRaw('coalesce(b.service_zone_id, 0), b.service_category_id, b.currency')
            ->get();

        $recipients = DB::table('booking_dispatch_recipients as r')
            ->join('bookings as b', 'b.id', '=', 'r.booking_id')
            ->selectRaw('coalesce(b.service_zone_id, 0) as zone_id, b.service_category_id as category_id, b.currency,
                sum(r.notified_at is not null) as recipients_notified,
                sum(r.response is not null) as recipients_responded,
                sum(case when r.notified_at is not null and r.responded_at is not null then timestampdiff(second, r.notified_at, r.responded_at) else 0 end) as response_seconds_sum,
                sum(r.notified_at is not null and r.responded_at is not null) as response_seconds_count')
            ->where('b.city_id', $cityId)
            ->where('b.created_at', '>=', $startUtc)
            ->where('b.created_at', '<', $endUtc)
            ->where('b.status', '!=', BookingStatus::Draft->value)
            ->groupByRaw('coalesce(b.service_zone_id, 0), b.service_category_id, b.currency')
            ->get();

        return $this->merge([$requests, $offers, $recipients], ['zone_id', 'category_id', 'currency']);
    }

    public function financeFacts(int $cityId, CarbonInterface $startUtc, CarbonInterface $endUtc): array
    {
        $paidStatuses = $this->quoted([PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value, PaymentStatus::CashCollected->value]);

        $paid = DB::table('payments as p')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->selectRaw('b.service_category_id as category_id, p.currency, count(*) as payments_paid, coalesce(sum(p.amount_minor), 0) as payments_paid_minor')
            ->where('b.city_id', $cityId)
            ->where('p.kind', PaymentKind::Trip->value)
            ->whereRaw("p.status in ({$paidStatuses})")
            ->where('p.paid_at', '>=', $startUtc)
            ->where('p.paid_at', '<', $endUtc)
            ->groupBy('b.service_category_id', 'p.currency')
            ->get();

        $failed = DB::table('payments as p')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->selectRaw('b.service_category_id as category_id, p.currency, count(*) as payments_failed, coalesce(sum(p.amount_minor), 0) as payments_failed_minor')
            ->where('b.city_id', $cityId)
            ->where('p.kind', PaymentKind::Trip->value)
            ->where('p.status', PaymentStatus::Failed->value)
            ->whereRaw('coalesce(p.last_event_at, p.updated_at) >= ?', [$startUtc])
            ->whereRaw('coalesce(p.last_event_at, p.updated_at) < ?', [$endUtc])
            ->groupBy('b.service_category_id', 'p.currency')
            ->get();

        $refunds = DB::table('payment_refunds as r')
            ->join('payments as p', 'p.id', '=', 'r.payment_id')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->selectRaw('b.service_category_id as category_id, r.currency, coalesce(sum(r.amount_minor), 0) as refunds_minor')
            ->where('b.city_id', $cityId)
            ->where('r.status', RefundStatus::Completed->value)
            ->where('r.completed_at', '>=', $startUtc)
            ->where('r.completed_at', '<', $endUtc)
            ->groupBy('b.service_category_id', 'r.currency')
            ->get();

        $settlements = $this->quoted([JournalEntryType::BookingSettlementCash->value, JournalEntryType::BookingSettlementGateway->value]);

        $ledger = DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('ledger_accounts as a', 'a.id', '=', 'l.ledger_account_id')
            ->join('bookings as b', function ($join): void {
                $join->on('b.id', '=', 'e.reference_id')->where('e.reference_type', '=', 'booking');
            })
            ->selectRaw("b.service_category_id as category_id, l.currency,
                coalesce(sum(case when a.type = ? then (case when l.side = 'credit' then l.amount_minor else -l.amount_minor end) else 0 end), 0) as commission_minor,
                coalesce(sum(case when a.type = ? then (case when l.side = 'credit' then l.amount_minor else -l.amount_minor end) else 0 end), 0) as provider_earnings_minor", [LedgerAccountType::PlatformRevenue->value, LedgerAccountType::ProviderWallet->value])
            ->where('b.city_id', $cityId)
            ->where('e.posted_at', '>=', $startUtc)
            ->where('e.posted_at', '<', $endUtc)
            ->whereRaw("(e.type in ({$settlements}) or (e.type = ? and exists (select 1 from journal_entries o where o.id = e.reversal_of_id and o.type in ({$settlements}))))", [JournalEntryType::Reversal->value])
            ->groupBy('b.service_category_id', 'l.currency')
            ->get();

        $coupons = DB::table('coupon_redemptions as c')
            ->join('bookings as b', 'b.id', '=', 'c.booking_id')
            ->selectRaw('b.service_category_id as category_id, c.currency, count(*) as coupon_redemptions, coalesce(sum(c.discount_minor), 0) as coupon_discount_minor, coalesce(sum(c.platform_minor), 0) as coupon_platform_minor, coalesce(sum(c.fare_minor), 0) as coupon_fare_minor')
            ->where('b.city_id', $cityId)
            ->where('c.status', CouponRedemptionStatus::Committed->value)
            ->where('c.redeemed_at', '>=', $startUtc)
            ->where('c.redeemed_at', '<', $endUtc)
            ->groupBy('b.service_category_id', 'c.currency')
            ->get();

        return $this->merge([$paid, $failed, $refunds, $ledger, $coupons], ['category_id', 'currency']);
    }

    public function operationsFacts(CarbonInterface $startUtc, CarbonInterface $endUtc): array
    {
        $count = static fn (string $table, string $column, ?callable $extra = null): int => (int) DB::table($table)
            ->where($column, '>=', $startUtc)->where($column, '<', $endUtc)
            ->when($extra !== null, static fn ($query) => $extra($query))
            ->count();

        $firstResponse = DB::table('support_tickets')
            ->selectRaw('coalesce(sum(timestampdiff(second, created_at, first_response_at)), 0) as seconds, count(*) as total')
            ->where('first_response_at', '>=', $startUtc)->where('first_response_at', '<', $endUtc)
            ->first();

        $urgent = [IncidentSeverity::High->value, IncidentSeverity::Critical->value];

        return [
            'tickets_opened' => $count('support_tickets', 'created_at'),
            'tickets_resolved' => $count('support_tickets', 'resolved_at'),
            'tickets_sla_breached' => $count('support_tickets', 'sla_breached_at'),
            'first_response_seconds_sum' => (int) ($firstResponse->seconds ?? 0),
            'first_response_count' => (int) ($firstResponse->total ?? 0),
            'incidents_opened' => $count('safety_incidents', 'created_at'),
            'incidents_urgent' => $count('safety_incidents', 'created_at', static fn ($query) => $query->whereIn('severity', $urgent)),
            'incidents_resolved' => $count('safety_incidents', 'resolved_at'),
            'incidents_sla_breached' => $count('safety_incidents', 'sla_breached_at'),
            'new_customers' => $count('customer_profiles', 'created_at'),
            'new_providers' => $count('provider_profiles', 'created_at'),
            'active_customers' => (int) DB::table('bookings')
                ->where('created_at', '>=', $startUtc)->where('created_at', '<', $endUtc)
                ->where('status', '!=', BookingStatus::Draft->value)
                ->distinct()->count('customer_id'),
        ];
    }

    public function replaceBookingMetrics(string $date, int $cityId, array $rows, CarbonInterface $now): int
    {
        return $this->db->transaction(function () use ($date, $cityId, $rows, $now): int {
            MetricDailyBooking::query()->where('metric_date', $date)->where('city_id', $cityId)->delete();

            $payload = array_map(static fn (array $row): array => array_merge($row, ['metric_date' => $date, 'city_id' => $cityId, 'computed_at' => $now]), $rows);

            foreach (array_chunk($payload, 200) as $chunk) {
                MetricDailyBooking::query()->insert($chunk);
            }

            return count($payload);
        });
    }

    public function replaceFinanceMetrics(string $date, int $cityId, array $rows, CarbonInterface $now): int
    {
        return $this->db->transaction(function () use ($date, $cityId, $rows, $now): int {
            MetricDailyFinance::query()->where('metric_date', $date)->where('city_id', $cityId)->delete();

            $payload = array_map(static fn (array $row): array => array_merge($row, ['metric_date' => $date, 'city_id' => $cityId, 'computed_at' => $now]), $rows);

            foreach (array_chunk($payload, 200) as $chunk) {
                MetricDailyFinance::query()->insert($chunk);
            }

            return count($payload);
        });
    }

    public function replaceOperationsMetrics(string $date, string $timezone, array $facts, CarbonInterface $now): int
    {
        MetricDailyOperation::query()->upsert([array_merge($facts, ['metric_date' => $date, 'timezone' => $timezone, 'computed_at' => $now])], ['metric_date', 'timezone'], array_keys(array_merge($facts, ['computed_at' => $now])));

        return 1;
    }

    public function recordRun(MetricSet $set, CarbonInterface $at, string $coveredThrough, int $rows, int $durationMs): void
    {
        MetricAggregationState::query()->updateOrCreate(
            ['metric_set' => $set->value],
            ['last_aggregated_at' => $at, 'covered_through' => $coveredThrough, 'rows' => $rows, 'duration_ms' => $durationMs, 'updated_at' => $at],
        );
    }

    /**
     * Full-outer merge of grouped result sets on the dimension keys (missing measures stay 0).
     *
     * @param list<\Illuminate\Support\Collection<int, stdClass>> $sets
     * @param list<string> $keys
     * @return list<array<string, mixed>>
     */
    private function merge(array $sets, array $keys): array
    {
        $merged = [];

        foreach ($sets as $set) {
            foreach ($set as $row) {
                $row = (array) $row;
                $id = implode('|', array_map(static fn (string $key): string => (string) $row[$key], $keys));
                $merged[$id] = array_merge($merged[$id] ?? [], $row);
            }
        }

        $measures = array_values(array_unique(array_merge([], ...array_values(array_map(static fn (array $row): array => array_diff(array_keys($row), $keys), $merged)))));

        return array_values(array_map(static function (array $row) use ($measures, $keys): array {
            foreach ($measures as $measure) {
                if (! in_array($measure, $keys, true)) {
                    $row[$measure] = (int) ($row[$measure] ?? 0);
                }
            }

            return $row;
        }, $merged));
    }

    /**
     * @param list<string> $values
     */
    private function quoted(array $values): string
    {
        return "'".implode("','", array_map(static fn (string $value): string => str_replace("'", '', $value), $values))."'";
    }
}
