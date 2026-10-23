<?php

namespace App\Http\Requests\Admin\Reporting;

use App\Enums\Reporting\ReportKey;
use Illuminate\Support\Facades\Gate;

/**
 * Queues the CSV export of a report: same filter rules as the page; the policy `export` (reports.export + the report's own permission).
 */
class ReportExportRequest extends DashboardFilterRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof ReportKey && Gate::allows('export', $report);
    }
}
