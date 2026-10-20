<?php

namespace App\Http\Controllers\Admin\Customers;

use App\DataTables\Admin\Customers\CustomerAuditDataTable;
use App\DataTables\Admin\Customers\CustomerConsentsDataTable;
use App\DataTables\Admin\Customers\CustomerDeletionRequestsDataTable;
use App\DataTables\Admin\Customers\CustomerDevicesDataTable;
use App\DataTables\Admin\Customers\CustomerMessagesDataTable;
use App\DataTables\Admin\Customers\CustomerSavedPlacesDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Models\User;

/**
 * Lazy-loaded tabs of the customer show page. Each one is its own Yajra DataTable served from its own URL:
 * a normal request returns the HTML fragment, the DataTables AJAX call (JSON) returns the rows.
 */
final class CustomerTabController extends AdminController
{
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

    public function audit(CustomerAuditDataTable $table, User $customer): mixed
    {
        return $table->render('admin.customers.tabs.audit', ['customer' => $customer]);
    }
}
