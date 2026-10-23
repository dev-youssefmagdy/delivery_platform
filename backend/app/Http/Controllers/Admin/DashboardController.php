<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Reporting\ReportFilterData;
use App\Http\Requests\Admin\Reporting\DashboardFilterRequest;
use App\Repositories\Contracts\Reporting\ReportingRepositoryInterface;
use App\Services\Admin\DashboardService;
use App\Services\Reporting\OperationsDashboardService;
use Illuminate\Contracts\View\View;

/**
 * Operations dashboard (SRS 16.1): permission-aware KPI tiles and charts for a market-day range and timezone. The filter form validates with
 * Precognition and then asks for the widgets over AJAX (the same URL returns only the widgets partial), so the URL stays shareable.
 */
final class DashboardController extends AdminController
{
    public function __invoke(DashboardFilterRequest $request, DashboardService $dashboard, OperationsDashboardService $operations, ReportingRepositoryInterface $reporting): View
    {
        $filters = ReportFilterData::fromRequest($request);
        $data = $operations->build($this->actor(), $filters);

        if ($request->ajax()) {
            return view('admin.dashboard._widgets', ['board' => $data]);
        }

        return view('admin.dashboard', [
            'overview' => $dashboard->overview($this->actor()),
            'board' => $data,
            'timezones' => $reporting->marketTimezones(),
        ]);
    }
}
