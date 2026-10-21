<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Provider\ProviderAuditDataTable;
use App\DataTables\Admin\Provider\ProviderDeletionRequestsDataTable;
use App\DataTables\Admin\Provider\ProviderDocumentsDataTable;
use App\DataTables\Admin\Provider\ProviderHistoryDataTable;
use App\DataTables\Admin\Provider\ProviderJobsDataTable;
use App\DataTables\Admin\Provider\ProviderServicesDataTable;
use App\DataTables\Admin\Provider\ProviderVehiclesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\ProviderProfile;
use App\Services\Booking\ProviderJobHistoryService;
use Illuminate\Contracts\View\View;

/**
 * Lazy-loaded tabs of the provider show page. Each one is its own Yajra DataTable served from its own URL: a normal
 * request returns the HTML fragment, the DataTables AJAX call (JSON) returns the rows.
 */
final class ProviderTabController extends AdminController
{
    public function __construct(private readonly ProviderJobHistoryService $jobs) {}

    public function services(ProviderServicesDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.services.intro')]);
    }

    public function vehicles(ProviderVehiclesDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.vehicles.intro')]);
    }

    public function documents(ProviderDocumentsDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.documents.intro')]);
    }

    public function history(ProviderHistoryDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.history.intro')]);
    }

    public function jobs(ProviderJobsDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.jobs.intro')]);
    }

    /**
     * Gross figures from completed jobs only: commission, wallet and payouts arrive with finance (P3.4), so no net is shown.
     */
    public function earnings(ProviderProfile $provider): View
    {
        return view('admin.providers.tabs.earnings', ['totals' => $this->jobs->completedTotals($provider)]);
    }

    public function deletionRequests(ProviderDeletionRequestsDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.deletion.intro')]);
    }

    public function audit(ProviderAuditDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.audit', ['provider' => $provider]);
    }
}
