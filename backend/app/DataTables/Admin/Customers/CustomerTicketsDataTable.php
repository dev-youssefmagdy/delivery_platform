<?php

namespace App\DataTables\Admin\Customers;

use App\DataTables\Admin\Support\SupportTicketsDataTable;
use App\DTOs\Support\TicketFilterData;
use App\Models\User;
use LogicException;

/**
 * The Tickets tab of the customer page: the same inbox rows, locked to this customer and still restricted to the tickets the
 * signed-in admin may see.
 */
final class CustomerTicketsDataTable extends SupportTicketsDataTable
{
    public const TAB_TABLE_ID = 'customer-tickets-table';

    public function tableId(): string
    {
        return self::TAB_TABLE_ID;
    }

    protected function filters(): TicketFilterData
    {
        $customer = $this->request()->route('customer');

        return TicketFilterData::fromRequest($this->filterRequest())->forUser($customer instanceof User ? $customer->id : throw new LogicException('The Tickets tab needs a {customer} route parameter.'));
    }
}
