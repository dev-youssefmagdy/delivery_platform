<?php

namespace App\DataTables\Admin\Payments;

use App\DTOs\Payments\RefundFilterData;
use App\Models\Payment;
use LogicException;

/**
 * Refunds tab of the payment page: the refunds of the payment in the `{payment}` route parameter.
 */
final class PaymentRefundsTabDataTable extends RefundsDataTable
{
    public const TABLE_ID = 'payment-refunds-table';

    protected function scoped(RefundFilterData $filters): RefundFilterData
    {
        $payment = $this->request()->route('payment');

        return $payment instanceof Payment ? $filters->forPayment($payment->id) : throw new LogicException('The refunds tab needs a {payment} route parameter.');
    }
}
