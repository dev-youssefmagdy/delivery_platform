<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Finance\ProviderWalletDataTable;
use App\DataTables\Admin\Payout\ProviderPayoutsDataTable;
use App\DataTables\Admin\Provider\ProviderAuditDataTable;
use App\DataTables\Admin\Provider\ProviderDeletionRequestsDataTable;
use App\DataTables\Admin\Provider\ProviderDocumentsDataTable;
use App\DataTables\Admin\Provider\ProviderEarningsDataTable;
use App\DataTables\Admin\Provider\ProviderHistoryDataTable;
use App\DataTables\Admin\Provider\ProviderJobsDataTable;
use App\DataTables\Admin\Provider\ProviderRatingsDataTable;
use App\DataTables\Admin\Provider\ProviderServicesDataTable;
use App\DataTables\Admin\Provider\ProviderVehiclesDataTable;
use App\DataTables\Admin\Risk\ProviderRiskSignalsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\ProviderProfile;
use App\Services\Finance\ProviderEarningsService;
use App\Services\Finance\WalletOverviewService;
use App\Services\Payout\PayoutMethodService;

/**
 * Lazy-loaded tabs of the provider show page. Each one is its own Yajra DataTable served from its own URL: a normal
 * request returns the HTML fragment, the DataTables AJAX call (JSON) returns the rows.
 */
final class ProviderTabController extends AdminController
{
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
     * Earnings (finance figures from the stored price breakdowns): the day / week / month overviews with the wallet position, the
     * by-category split of the month and the booking-level table.
     */
    public function earnings(ProviderEarningsDataTable $table, ProviderProfile $provider, ProviderEarningsService $earnings): mixed
    {
        $summaries = [];

        foreach (ProviderEarningsService::PERIODS as $period) {
            $summaries[$period] = $earnings->summary($provider, $period);
        }

        return $table->render('admin.providers.tabs.earnings', ['summaries' => $summaries, 'provider' => $provider]);
    }

    /**
     * Payouts (payouts.view): payout methods with their verification, and the provider's payout requests.
     */
    public function payouts(ProviderPayoutsDataTable $table, ProviderProfile $provider, PayoutMethodService $methods): mixed
    {
        return $table->render('admin.providers.tabs.payouts', ['provider' => $provider, 'methods' => $methods->list($provider)]);
    }

    /**
     * Wallet of the provider (finance.view or wallets.view): balance, cash debt against the limit and the statement.
     */
    public function wallet(ProviderWalletDataTable $table, ProviderProfile $provider, WalletOverviewService $wallets): mixed
    {
        return $table->render('admin.finance.wallet-tab', ['intro' => __('admin.ledger.wallet.provider_intro'), 'overview' => $wallets->forProvider($provider)]);
    }

    public function deletionRequests(ProviderDeletionRequestsDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.deletion.intro')]);
    }

    public function ratings(ProviderRatingsDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('admin.providers.ratings.intro')]);
    }

    public function risk(ProviderRiskSignalsDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.table', ['intro' => __('reputation.admin.provider_risk_intro')]);
    }

    public function audit(ProviderAuditDataTable $table, ProviderProfile $provider): mixed
    {
        return $table->render('admin.providers.tabs.audit', ['provider' => $provider]);
    }
}
