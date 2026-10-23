<?php

namespace App\Http\Controllers\Admin\Reporting;

use App\DataTables\Admin\Reporting\ReportsDataTable;
use App\DTOs\Reporting\ReportFilterData;
use App\Enums\Reporting\ReportKey;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Reporting\ReportExportRequest;
use App\Repositories\Contracts\Reporting\ReportingRepositoryInterface;
use App\Services\Admin\AdminExportService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

/**
 * Reports (SRS 16.14): the hub of reports the admin may open, one page per report (collapsed filters card, chart, Yajra table) and the
 * queued, audited CSV export (`reports.export`; personal-data columns only with `reports.export_pii`).
 */
final class ReportController extends AdminController
{
    public function index(): View
    {
        $reports = array_values(array_filter(ReportKey::cases(), static fn (ReportKey $report): bool => Gate::allows('view', $report)));
        $groups = [];

        foreach ($reports as $report) {
            $groups[$report->group()][] = $report;
        }

        return view('admin.reports.index', ['groups' => $groups]);
    }

    public function show(ReportKey $report, ReportsDataTable $table, ReportingRepositoryInterface $reporting): mixed
    {
        if ($report->isPlaceholder()) {
            return view('admin.reports.show', ['report' => $report, 'dataTable' => null, 'filters' => ReportFilterData::fromRequest(request()), 'timezones' => [], 'asOf' => null]);
        }

        $set = $report->metricSet();

        return $table->render('admin.reports.show', [
            'report' => $report,
            'filters' => ReportFilterData::fromRequest(request()),
            'timezones' => $reporting->marketTimezones(),
            'asOf' => $set === null ? null : $reporting->lastAggregatedAt($set),
        ]);
    }

    public function export(ReportExportRequest $request, ReportKey $report, ReportsDataTable $table, AdminExportService $exports): AdminFormResponse
    {
        $filters = ReportFilterData::fromRequest($request)->withPii(Gate::allows('viewPii', $report));
        $stored = $filters->toArray() + ['report' => $report->value];
        $export = $exports->queue($table->usingFilters($stored), $stored, $this->actor());

        return $this->formResponse(__('admin.exports.queued'))->with('status_url', route('admin.exports.show', $export));
    }
}
