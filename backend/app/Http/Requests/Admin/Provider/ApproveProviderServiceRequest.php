<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve one requested service of an approved provider: mandatory reason.
 */
class ApproveProviderServiceRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'decideService';
    }
}
