<?php

namespace App\Repositories\Eloquent\Reporting;

use App\DTOs\Reporting\ReportFilterData;
use App\Enums\Booking\BookingStatus;
use App\Enums\Coupon\CouponRedemptionStatus;
use App\Enums\Finance\JournalEntryType;
use App\Enums\Finance\LedgerAccountType;
use App\Enums\Payout\PayoutStatus;
use App\Enums\Provider\DocumentStatus;
use App\Enums\Reporting\ReportGrouping;
use App\Enums\Reporting\ReportKey;
use App\Models\Booking;
use App\Models\City;
use App\Models\CouponRedemption;
use App\Models\JournalLine;
use App\Models\MetricDailyBooking;
use App\Models\MetricDailyFinance;
use App\Models\MetricDailyOperation;
use App\Models\PaymentReconciliationRun;
use App\Models\PayoutRequest;
use App\Models\ProviderDocument;
use App\Models\ReferralRelationship;
use App\Repositories\Contracts\Reporting\ReportQueryRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Report queries (ADR-178). Select aliases are the report columns; every query is bounded by the date range or by a small open set
 * (open payouts, documents). Money is always in integer minor units next to its currency column.
 */
final class ReportQueryRepository implements ReportQueryRepositoryInterface
{
    public function datatableQuery(ReportKey $report, ReportFilterData $filters, bool $includePii = false): Builder
    {
        return match ($report) {
            ReportKey::BookingFunnel => $this->bookingMetrics($filters, [
                'sum(m.requests) as requests', 'sum(m.with_offers) as with_offers', 'sum(m.selected) as selected', 'sum(m.completed) as completed',
                $this->pct('sum(m.with_offers)', 'sum(m.requests)').' as request_to_offer',
                $this->pct('sum(m.selected)', 'sum(m.with_offers)').' as offer_to_selection',
                $this->pct('sum(m.completed)', 'sum(m.selected)').' as selection_to_completion',
                $this->pct('sum(m.completed)', 'sum(m.requests)').' as overall_conversion',
            ]),
            ReportKey::Fares => $this->bookingMetrics($filters, [
                'sum(m.requests) as requests',
                'round(sum(m.proposed_fare_sum_minor) / nullif(sum(m.requests), 0)) as avg_proposed',
                'sum(m.offers_submitted) as offers_submitted',
                'round(sum(m.offered_fare_sum_minor) / nullif(sum(m.offers_submitted), 0)) as avg_offered',
                'sum(m.selected) as selected',
                'round(sum(m.agreed_fare_sum_minor) / nullif(sum(m.selected), 0)) as avg_agreed',
                'round(100 * (sum(m.agreed_fare_sum_minor) / nullif(sum(m.selected), 0)) / (sum(m.proposed_fare_sum_minor) / nullif(sum(m.requests), 0)), 1) as agreed_vs_proposed',
            ], withCurrency: true),
            ReportKey::ProviderAcceptance => $this->bookingMetrics($filters, [
                'sum(m.recipients_notified) as recipients_notified', 'sum(m.recipients_responded) as recipients_responded',
                $this->pct('sum(m.recipients_responded)', 'sum(m.recipients_notified)').' as response_rate',
                'sum(m.offers_submitted) as offers_submitted',
                $this->pct('sum(m.offers_submitted)', 'sum(m.recipients_notified)').' as offer_rate',
                'sum(m.selected) as selected',
                $this->pct('sum(m.selected)', 'sum(m.offers_submitted)').' as selection_rate',
                'round(sum(m.response_seconds_sum) / nullif(sum(m.response_seconds_count), 0)) as avg_response_seconds',
            ]),
            ReportKey::Completion => $this->bookingMetrics($filters, [
                'sum(m.requests) as requests', 'sum(m.selected) as selected', 'sum(m.completed) as completed',
                $this->pct('sum(m.completed)', 'sum(m.requests)').' as completion_rate',
                $this->pct('sum(m.completed)', 'sum(m.selected)').' as completion_of_selected',
            ]),
            ReportKey::Cancellation => $this->bookingMetrics($filters, [
                'sum(m.requests) as requests', 'sum(m.cancelled_customer) as cancelled_customer', 'sum(m.cancelled_provider) as cancelled_provider',
                'sum(m.cancelled_operations) as cancelled_operations', 'sum(m.expired) as expired', 'sum(m.no_show) as no_show',
                $this->pct('sum(m.cancelled_customer + m.cancelled_provider + m.cancelled_operations)', 'sum(m.requests)').' as cancellation_rate',
                $this->pct('sum(m.no_show)', 'sum(m.requests)').' as no_show_rate',
            ]),
            ReportKey::Commissions => $this->commissions($filters),
            ReportKey::SupplyDemand => $this->supplyDemand($filters),
            ReportKey::CouponRoi => $this->couponRoi($filters),
            ReportKey::ProviderEarnings => $this->providerEarnings($filters, $includePii),
            ReportKey::PayoutAgeing => $this->payoutAgeing($filters),
            ReportKey::PaymentReconciliation => $this->paymentReconciliation($filters),
            ReportKey::SupportSla => $this->operations($filters, [
                'sum(m.tickets_opened) as tickets_opened', 'sum(m.tickets_resolved) as tickets_resolved', 'sum(m.tickets_sla_breached) as tickets_sla_breached',
                $this->pct('sum(m.tickets_sla_breached)', 'sum(m.tickets_opened)').' as breach_rate',
                'round(sum(m.first_response_seconds_sum) / nullif(sum(m.first_response_count), 0)) as avg_first_response_seconds',
            ]),
            ReportKey::SafetyTrends => $this->operations($filters, [
                'sum(m.incidents_opened) as incidents_opened', 'sum(m.incidents_urgent) as incidents_urgent',
                'sum(m.incidents_resolved) as incidents_resolved', 'sum(m.incidents_sla_breached) as incidents_sla_breached',
            ]),
            ReportKey::DocumentExpiry => $this->documentExpiry(),
            ReportKey::Retention => $this->retention($filters),
            ReportKey::Referral => $this->referralOutcomes($filters),
        };
    }

    public function chartRows(ReportKey $report, ReportFilterData $filters): Collection
    {
        if ($report === ReportKey::PaymentReconciliation) {
            return MetricDailyFinance::query()
                ->whereBetween('metric_date', $this->dates($filters))
                ->selectRaw("date_format(metric_date, '%Y-%m-%d') as bucket_label, sum(payments_paid) as payments_paid, sum(payments_failed) as payments_failed")
                ->groupBy('metric_date')->orderBy('metric_date')->limit(400)->get();
        }

        $query = $this->datatableQuery($report, $filters);
        $first = $report->chartSeries()[0][0] ?? null;

        if ($report->isTimeSeries($filters->grouping) && in_array($report, [ReportKey::BookingFunnel, ReportKey::Fares, ReportKey::ProviderAcceptance, ReportKey::Completion, ReportKey::Cancellation, ReportKey::Commissions, ReportKey::SupportSla, ReportKey::SafetyTrends], true)) {
            return $query->reorder()->orderBy('bucket')->limit(400)->get();
        }

        if ($report === ReportKey::Retention) {
            return $query->reorder()->orderByDesc('cohort')->limit(24)->get()->reverse()->values();
        }

        return $first === null ? $query->limit(24)->get() : $query->reorder()->orderByDesc($first)->limit(24)->get();
    }

    /**
     * @param list<string> $measures
     * @return Builder<MetricDailyBooking>
     */
    private function bookingMetrics(ReportFilterData $filters, array $measures, bool $withCurrency = false): Builder
    {
        $query = MetricDailyBooking::query()->from('metrics_daily_bookings as m')
            ->whereBetween('m.metric_date', $this->dates($filters))
            ->when($filters->cityIds !== [], static fn (Builder $q) => $q->whereIn('m.city_id', $filters->cityIds))
            ->when($filters->zoneIds !== [], static fn (Builder $q) => $q->whereIn('m.service_zone_id', $filters->zoneIds))
            ->when($filters->categoryIds !== [], static fn (Builder $q) => $q->whereIn('m.service_category_id', $filters->categoryIds))
            ->when($filters->range->timezone !== null, static fn (Builder $q) => $q->whereIn('m.city_id', City::query()->where('timezone', $filters->range->timezone)->select('id')))
            ->when($filters->currency !== null, static fn (Builder $q) => $q->where('m.currency', $filters->currency));

        return $this->bucketed($query, $filters->grouping, $measures, $withCurrency, true);
    }

    /**
     * @param list<string> $measures
     * @return Builder<MetricDailyFinance>
     */
    private function financeBucketed(ReportFilterData $filters, array $measures): Builder
    {
        $query = MetricDailyFinance::query()->from('metrics_daily_finance as m')
            ->whereBetween('m.metric_date', $this->dates($filters))
            ->when($filters->cityIds !== [], static fn (Builder $q) => $q->whereIn('m.city_id', $filters->cityIds))
            ->when($filters->categoryIds !== [], static fn (Builder $q) => $q->whereIn('m.service_category_id', $filters->categoryIds))
            ->when($filters->range->timezone !== null, static fn (Builder $q) => $q->whereIn('m.city_id', City::query()->where('timezone', $filters->range->timezone)->select('id')))
            ->when($filters->currency !== null, static fn (Builder $q) => $q->where('m.currency', $filters->currency));

        return $this->bucketed($query, $filters->grouping, $measures, true, false);
    }

    private function commissions(ReportFilterData $filters): Builder
    {
        return $this->financeBucketed($filters, [
            'sum(m.payments_paid_minor) as collected_minor', 'sum(m.commission_minor) as commission_minor',
            $this->pct('sum(m.commission_minor)', 'sum(m.payments_paid_minor)').' as commission_rate',
            'sum(m.provider_earnings_minor) as provider_earnings_minor', 'sum(m.refunds_minor) as refunds_minor',
        ]);
    }

    /**
     * Adds the bucket (date / city / zone / category) with its label to a metrics query.
     *
     * @param Builder<covariant Model> $query
     * @param list<string> $measures
     * @return Builder<covariant Model>
     */
    private function bucketed(Builder $query, ReportGrouping $grouping, array $measures, bool $withCurrency, bool $hasZones): Builder
    {
        $grouping = $grouping === ReportGrouping::Zone && ! $hasZones ? ReportGrouping::Date : $grouping;
        $currency = $withCurrency ? ['m.currency'] : [];

        [$bucket, $label] = match ($grouping) {
            ReportGrouping::Date => ['m.metric_date', "date_format(m.metric_date, '%Y-%m-%d')"],
            ReportGrouping::City => ['m.city_id', $this->name('c.name')],
            ReportGrouping::Zone => ['m.service_zone_id', "coalesce({$this->name('z.name')}, '—')"],
            ReportGrouping::Category => ['m.service_category_id', "coalesce({$this->name('sc.name')}, '—')"],
        };

        match ($grouping) {
            ReportGrouping::City => $query->leftJoin('cities as c', 'c.id', '=', 'm.city_id'),
            ReportGrouping::Zone => $query->leftJoin('service_zones as z', 'z.id', '=', 'm.service_zone_id'),
            ReportGrouping::Category => $query->leftJoin('service_categories as sc', 'sc.id', '=', 'm.service_category_id'),
            default => null,
        };

        return $query
            ->selectRaw(implode(', ', array_merge(["{$bucket} as bucket", "{$label} as bucket_label"], $currency, $measures)))
            ->groupByRaw(implode(', ', array_merge([$bucket, $label], $currency)))
            ->orderBy('bucket');
    }

    /**
     * @param list<string> $measures
     * @return Builder<MetricDailyOperation>
     */
    private function operations(ReportFilterData $filters, array $measures): Builder
    {
        $timezone = $filters->range->timezone ?? (string) config('admin.display_timezone', 'UTC');

        return MetricDailyOperation::query()->from('metrics_daily_operations as m')
            ->whereBetween('m.metric_date', $this->dates($filters))
            ->where('m.timezone', $timezone)
            ->selectRaw(implode(', ', array_merge(['m.metric_date as bucket', "date_format(m.metric_date, '%Y-%m-%d') as bucket_label"], $measures)))
            ->groupBy('m.metric_date')->orderBy('bucket');
    }

    private function supplyDemand(ReportFilterData $filters): Builder
    {
        [$start, $end, $timezone] = $this->window($filters);
        $offset = (int) round(CarbonImmutable::parse($filters->range->from->toDateString(), $timezone)->utcOffset());

        return Booking::query()->from('bookings as b')
            ->leftJoin('service_zones as z', 'z.id', '=', 'b.service_zone_id')
            ->leftJoin('service_categories as sc', 'sc.id', '=', 'b.service_category_id')
            ->leftJoin('booking_offers as o', 'o.booking_id', '=', 'b.id')
            ->where('b.created_at', '>=', $start)->where('b.created_at', '<', $end)
            ->where('b.status', '!=', BookingStatus::Draft->value)
            ->when($filters->cityIds !== [], static fn (Builder $q) => $q->whereIn('b.city_id', $filters->cityIds))
            ->when($filters->zoneIds !== [], static fn (Builder $q) => $q->whereIn('b.service_zone_id', $filters->zoneIds))
            ->when($filters->categoryIds !== [], static fn (Builder $q) => $q->whereIn('b.service_category_id', $filters->categoryIds))
            ->when($filters->range->timezone !== null, static fn (Builder $q) => $q->whereIn('b.city_id', City::query()->where('timezone', $filters->range->timezone)->select('id')))
            ->selectRaw("coalesce({$this->name('z.name')}, '—') as zone_label, coalesce({$this->name('sc.name')}, '—') as category_label,
                hour(date_add(b.created_at, interval {$offset} minute)) as hour_of_day,
                count(distinct b.id) as requests,
                count(distinct case when o.id is not null then b.id end) as with_offers,
                count(distinct case when b.status = ? then b.id end) as unmet,
                count(distinct o.provider_profile_id) as offering_providers,
                ".$this->pct('count(distinct case when o.id is not null then b.id end)', 'count(distinct b.id)').' as fill_rate', [BookingStatus::ExpiredNoProvider->value])
            ->groupByRaw("b.service_zone_id, b.service_category_id, hour(date_add(b.created_at, interval {$offset} minute)), zone_label, category_label")
            ->orderByDesc('requests');
    }

    private function couponRoi(ReportFilterData $filters): Builder
    {
        [$start, $end] = $this->window($filters);

        return CouponRedemption::query()->from('coupon_redemptions as cr')
            ->join('coupons as c', 'c.id', '=', 'cr.coupon_id')
            ->join('bookings as b', 'b.id', '=', 'cr.booking_id')
            ->where('cr.status', CouponRedemptionStatus::Committed->value)
            ->where('cr.redeemed_at', '>=', $start)->where('cr.redeemed_at', '<', $end)
            ->when($filters->cityIds !== [], static fn (Builder $q) => $q->whereIn('b.city_id', $filters->cityIds))
            ->when($filters->categoryIds !== [], static fn (Builder $q) => $q->whereIn('b.service_category_id', $filters->categoryIds))
            ->when($filters->currency !== null, static fn (Builder $q) => $q->where('cr.currency', $filters->currency))
            ->selectRaw("coalesce(c.code, concat('#', c.id)) as coupon_code, cr.currency, count(*) as redemptions, sum(cr.discount_minor) as discount_minor, sum(cr.platform_minor) as platform_minor, sum(cr.fare_minor) as fare_minor, round(sum(cr.fare_minor) / nullif(sum(cr.platform_minor), 0), 2) as fare_per_spend")
            ->groupBy('c.id', 'c.code', 'cr.currency')
            ->orderByDesc('redemptions');
    }

    private function providerEarnings(ReportFilterData $filters, bool $includePii): Builder
    {
        [$start, $end] = $this->window($filters);
        $settlements = "'".JournalEntryType::BookingSettlementCash->value."','".JournalEntryType::BookingSettlementGateway->value."'";
        $name = $includePii ? '(select u.name from provider_profiles pp join users u on u.id = pp.user_id where pp.id = a.owner_id)' : 'null';

        return JournalLine::query()->from('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('ledger_accounts as a', 'a.id', '=', 'l.ledger_account_id')
            ->where('a.type', LedgerAccountType::ProviderWallet->value)
            ->where('a.owner_type', 'provider_profile')
            ->whereRaw("e.type in ({$settlements})")
            ->where('e.posted_at', '>=', $start)->where('e.posted_at', '<', $end)
            ->when($filters->currency !== null, static fn (Builder $q) => $q->where('l.currency', $filters->currency))
            ->selectRaw("concat('#', a.owner_id) as provider_ref, {$name} as provider_name, l.currency,
                count(distinct e.reference_id) as jobs,
                sum(case when l.side = 'credit' then l.amount_minor else -l.amount_minor end) as earnings_minor,
                (select coalesce(sum(pr.net_minor), 0) from payout_requests pr where pr.provider_profile_id = a.owner_id and pr.currency = l.currency and pr.status = ? and pr.paid_at >= ? and pr.paid_at < ?) as payouts_paid_minor", [PayoutStatus::Paid->value, $start, $end])
            ->groupBy('a.owner_id', 'l.currency')
            ->orderByDesc('earnings_minor');
    }

    private function payoutAgeing(ReportFilterData $filters): Builder
    {
        $open = array_map(static fn (PayoutStatus $status): string => $status->value, PayoutStatus::open());
        $age = 'timestampdiff(day, p.created_at, utc_timestamp())';
        $bucket = "case when {$age} < 2 then '0-1' when {$age} < 4 then '2-3' when {$age} < 8 then '4-7' else '8+' end";

        return PayoutRequest::query()->from('payout_requests as p')
            ->whereIn('p.status', $open)
            ->when($filters->currency !== null, static fn (Builder $q) => $q->where('p.currency', $filters->currency))
            ->selectRaw("p.status as status_label, {$bucket} as age_bucket, p.currency, count(*) as requests, sum(p.amount_minor) as amount_minor, max({$age}) as oldest_days")
            ->groupByRaw("p.status, {$bucket}, p.currency")
            ->orderByDesc('oldest_days');
    }

    private function paymentReconciliation(ReportFilterData $filters): Builder
    {
        [$start, $end] = $this->window($filters);

        return PaymentReconciliationRun::query()->from('payment_reconciliation_runs as r')
            ->where('r.ran_at', '>=', $start)->where('r.ran_at', '<', $end)
            ->selectRaw("r.id as id, date_format(r.ran_at, '%Y-%m-%d %H:%i') as ran_at, r.status as status_label, r.payments_checked, r.transactions_checked, r.timed_out, coalesce(json_length(r.findings), 0) as findings_count")
            ->orderByDesc('r.ran_at');
    }

    private function documentExpiry(): Builder
    {
        $today = now((string) config('admin.display_timezone', 'UTC'))->toDateString();

        return ProviderDocument::query()->from('provider_documents as d')
            ->join('document_types as t', 't.id', '=', 'd.document_type_id')
            ->whereNull('d.deleted_at')
            ->where('d.status', DocumentStatus::Approved->value)
            ->whereNotNull('d.expires_at')
            ->selectRaw("{$this->name('t.name')} as document_type,
                sum(d.expires_at < ?) as expired,
                sum(d.expires_at >= ? and d.expires_at <= date_add(?, interval 7 day)) as expiring_7,
                sum(d.expires_at > date_add(?, interval 7 day) and d.expires_at <= date_add(?, interval 30 day)) as expiring_30,
                sum(d.expires_at > date_add(?, interval 30 day) and d.expires_at <= date_add(?, interval 60 day)) as expiring_60,
                count(*) as total_active", array_fill(0, 7, $today))
            ->groupBy('t.id', 't.name')
            ->orderByDesc('expired');
    }

    /**
     * Referral outcomes per campaign for referrals created inside the range: referrals, milestone reached, rewarded, under review,
     * rejected, conversion (rewarded / referrals) and the money spent from the campaign budget (all time, it is the campaign's own counter).
     */
    private function referralOutcomes(ReportFilterData $filters): Builder
    {
        [$start, $end] = $this->window($filters);

        return ReferralRelationship::query()->from('referral_relationships as r')
            ->join('referral_campaigns as c', 'c.id', '=', 'r.campaign_id')
            ->where('r.created_at', '>=', $start)->where('r.created_at', '<', $end)
            ->when($filters->currency !== null, static fn (Builder $q) => $q->where('c.currency', $filters->currency))
            ->selectRaw("c.name as campaign_name, case c.audience when 'customer' then ? else ? end as audience_label, c.currency,
                count(*) as referrals, sum(r.qualified_at is not null) as qualified, sum(r.status = 'rewarded') as rewarded,
                sum(r.status = 'under_review') as under_review, sum(r.status = 'rejected') as rejected,
                {$this->pct("sum(r.status = 'rewarded')", 'count(*)')} as conversion_rate, c.spent_minor, c.budget_minor",
                [(string) __('growth.enums.audience.customer'), (string) __('growth.enums.audience.provider')])
            ->groupBy('c.id', 'c.name', 'c.audience', 'c.currency', 'c.spent_minor', 'c.budget_minor')
            ->orderByDesc('referrals');
    }

    private function retention(ReportFilterData $filters): Builder
    {
        [$start, $end, $timezone] = $this->window($filters);
        $offset = (int) round(CarbonImmutable::parse($filters->range->from->toDateString(), $timezone)->utcOffset());
        $first = DB::table('bookings')->where('status', '!=', BookingStatus::Draft->value)->selectRaw('customer_id, min(created_at) as first_at')->groupBy('customer_id');
        $local = static fn (string $column): string => "date_add({$column}, interval {$offset} minute)";
        $index = static fn (string $column): string => "(year({$local($column)}) * 12 + month({$local($column)}))";
        $diff = "({$index('b.created_at')} - {$index('f.first_at')})";

        return Booking::query()->from('bookings as b')
            ->joinSub($first, 'f', 'f.customer_id', '=', 'b.customer_id')
            ->where('b.status', '!=', BookingStatus::Draft->value)
            ->where('f.first_at', '>=', $start)->where('f.first_at', '<', $end)
            ->selectRaw("date_format({$local('f.first_at')}, '%Y-%m') as cohort, count(distinct b.customer_id) as customers,
                count(distinct case when {$diff} = 1 then b.customer_id end) as m1,
                count(distinct case when {$diff} = 2 then b.customer_id end) as m2,
                count(distinct case when {$diff} = 3 then b.customer_id end) as m3,
                ".$this->pct("count(distinct case when {$diff} = 1 then b.customer_id end)", 'count(distinct b.customer_id)').' as m1_rate, '
                .$this->pct("count(distinct case when {$diff} = 2 then b.customer_id end)", 'count(distinct b.customer_id)').' as m2_rate, '
                .$this->pct("count(distinct case when {$diff} = 3 then b.customer_id end)", 'count(distinct b.customer_id)').' as m3_rate')
            ->groupByRaw("date_format({$local('f.first_at')}, '%Y-%m')")
            ->orderByDesc('cohort');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function dates(ReportFilterData $filters): array
    {
        return [$filters->range->from->toDateString(), $filters->range->to->toDateString()];
    }

    /**
     * UTC window of the range in the timezone filter (default: the admin display timezone) for the live reports.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    private function window(ReportFilterData $filters): array
    {
        $timezone = $filters->range->timezone ?? (string) config('admin.display_timezone', 'UTC');

        return [
            CarbonImmutable::parse($filters->range->from->toDateString(), $timezone)->startOfDay()->utc(),
            CarbonImmutable::parse($filters->range->to->toDateString(), $timezone)->addDay()->startOfDay()->utc(),
            $timezone,
        ];
    }

    private function pct(string $numerator, string $denominator): string
    {
        return "round(100 * {$numerator} / nullif({$denominator}, 0), 1)";
    }

    /**
     * Translated name of a JSON `{ar, en}` column in the current locale (falls back to the other language).
     */
    private function name(string $column): string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $other = $locale === 'en' ? 'ar' : 'en';

        return "coalesce(nullif(json_unquote(json_extract({$column}, '$.{$locale}')), ''), json_unquote(json_extract({$column}, '$.{$other}')))";
    }
}
