<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve a provider's deletion request (anonymises the account): mandatory reason.
 */
class ApproveProviderDeletionRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'processDeletion';
    }
}
