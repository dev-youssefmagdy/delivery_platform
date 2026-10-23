<?php

namespace App\Http\Controllers\Admin\Customers;

use App\DataTables\Admin\Customers\CustomerAuditDataTable;
use App\DataTables\Admin\Customers\CustomerBookingsDataTable;
use App\DataTables\Admin\Customers\CustomerConsentsDataTable;
use App\DataTables\Admin\Customers\CustomerDeletionRequestsDataTable;
use App\DataTables\Admin\Customers\CustomerDevicesDataTable;
use App\DataTables\Admin\Customers\CustomerMessagesDataTable;
use App\DataTables\Admin\Customers\CustomerRatingsDataTable;
use App\DataTables\Admin\Customers\CustomerSavedPlacesDataTable;
use App\DataTables\Admin\Risk\CustomerRiskSignalsDataTable;
use App\DataTables\Admin\Customers\CustomerTicketsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\DataTables\Admin\Finance\CustomerWalletDataTable;
use App\Models\User;
use App\Services\Finance\WalletOverviewService;

/**
 * Lazy-loaded tabs of the customer show page. Each one is its own Yajra DataTable served from its own URL:
 * a normal request returns the HTML fragment, the DataTables AJAX call (JSON) returns the rows.
 */
final class CustomerTabController extends AdminController
{
    public function bookings(CustomerBookingsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.bookings.intro')]);
    }

    /**
     * Wallet of the customer (finance.view or wallets.view): balances and the statement of the ledger account.
     */
    public function wallet(CustomerWalletDataTable $table, User $customer, WalletOverviewService $wallets): mixed
    {
        return $table->render('admin.finance.wallet-tab', ['intro' => __('admin.ledger.wallet.customer_intro'), 'overview' => $wallets->forCustomer($customer)]);
    }

    public function ratings(CustomerRatingsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.ratings.intro')]);
    }

    public function tickets(CustomerTicketsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.tickets.intro')]);
    }

    public function devices(CustomerDevicesDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.devices.intro')]);
    }

    public function consents(CustomerConsentsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.consents.intro')]);
    }

    public function savedPlaces(CustomerSavedPlacesDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.places.intro')]);
    }

    public function deletionRequests(CustomerDeletionRequestsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.deletion.intro')]);
    }

    public function messages(CustomerMessagesDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('admin.customers.messages.intro')]);
    }

    public function risk(CustomerRiskSignalsDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.table', ['intro' => __('reputation.admin.customer_risk_intro')]);
    }

    public function audit(CustomerAuditDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.audit', ['customer' => $customer]);
    }
}
