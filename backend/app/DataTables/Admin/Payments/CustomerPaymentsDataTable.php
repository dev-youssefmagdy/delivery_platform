<?php

namespace App\DataTables\Admin\Payments;

use App\DTOs\Payments\PaymentFilterData;
use App\Models\User;
use LogicException;

/**
 * Payments tab of the customer page. The customer comes from the `{customer}` route parameter, already authorized.
 */
final class CustomerPaymentsDataTable extends PaymentsDataTable
{
    public const TABLE_ID = 'customer-payments-table';

    protected function scoped(PaymentFilterData $filters): PaymentFilterData
    {
        $customer = $this->request()->route('customer');

        return $customer instanceof User ? $filters->forUser($customer->id) : throw new LogicException('The customer payments tab needs a {customer} route parameter.');
    }
}
