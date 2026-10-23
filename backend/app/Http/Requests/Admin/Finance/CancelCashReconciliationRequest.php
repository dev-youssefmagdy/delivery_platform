<?php

namespace App\Http\Requests\Admin\Finance;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Cancel an open cash reconciliation. Mandatory reason.
 */
class CancelCashReconciliationRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'cancel';
    }

    protected function parameter(): string
    {
        return 'cashReconciliation';
    }
}
