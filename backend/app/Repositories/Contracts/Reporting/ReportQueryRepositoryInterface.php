<?php

namespace App\Repositories\Contracts\Reporting;

use App\DTOs\Reporting\ReportFilterData;
use App\Enums\Reporting\ReportKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The queries behind the report pages (SRS 16.14). `datatableQuery()` is the one query used by the Yajra table AND the queued CSV
 * export; its select aliases are exactly `ReportKey::columns()`. Reports that read a metrics table are cheap sums; the live reports
 * (supply and demand, coupons, provider earnings, payout ageing, documents, retention) read the operational and ledger tables
 * bounded by the date range.
 */
interface ReportQueryRepositoryInterface
{
    /**
     * @return Builder<Model>
     */
    public function datatableQuery(ReportKey $report, ReportFilterData $filters, bool $includePii = false): Builder;

    /**
     * Rows for the chart (time ordered for time series, the biggest buckets otherwise); never more than 400.
     *
     * @return Collection<int, Model|object>
     */
    public function chartRows(ReportKey $report, ReportFilterData $filters): Collection;
}
