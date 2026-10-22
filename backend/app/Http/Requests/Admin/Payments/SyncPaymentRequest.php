<?php

namespace App\Http\Requests\Admin\Payments;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Ask the gateway for the truth about one payment and apply it (finance.reconcile). Mandatory reason; audited.
 */
class SyncPaymentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'sync';
    }

    protected function parameter(): string
    {
        return 'payment';
    }
}
