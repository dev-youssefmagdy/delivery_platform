<?php

namespace App\Http\Requests\Admin\Payments;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Process a failed / unmatched / mismatching webhook event again. Mandatory reason; audited.
 */
class ReprocessWebhookRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reprocess';
    }

    protected function parameter(): string
    {
        return 'gatewayWebhookEvent';
    }
}
