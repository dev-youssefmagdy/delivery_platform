<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject or revoke one service of an approved provider: mandatory reason.
 */
class RejectProviderServiceRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'decideService';
    }
}
