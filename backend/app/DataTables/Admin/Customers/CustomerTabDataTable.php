<?php

namespace App\DataTables\Admin\Customers;

use App\DataTables\Admin\AdminDataTable;
use App\Models\User;
use LogicException;

/**
 * Base of the DataTables inside the customer show page tabs. The customer comes from the `{customer}` route
 * parameter (already authorized by the route's `can:` middleware); global search is off.
 */
abstract class CustomerTabDataTable extends AdminDataTable
{
    protected bool $globalSearch = false;

    protected function customer(): User
    {
        $customer = $this->request()->route('customer');

        return $customer instanceof User ? $customer : throw new LogicException('Customer tab tables need a {customer} route parameter.');
    }
}
