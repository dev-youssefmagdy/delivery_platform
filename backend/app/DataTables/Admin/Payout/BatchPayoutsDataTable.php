<?php

namespace App\DataTables\Admin\Payout;

use App\DTOs\Payout\PayoutFilterData;
use App\Models\PayoutBatch;
use LogicException;

/**
 * The payouts of one batch (the batch page): scoped by the `{payoutBatch}` route parameter.
 */
final class BatchPayoutsDataTable extends PayoutRequestsDataTable
{
    public const TABLE_ID = 'batch-payouts-table';

    protected function scoped(PayoutFilterData $filters): PayoutFilterData
    {
        $batch = $this->request()->route('payoutBatch');

        return $batch instanceof PayoutBatch ? $filters->forBatch($batch->id) : throw new LogicException('The batch payouts table needs a {payoutBatch} route parameter.');
    }
}
