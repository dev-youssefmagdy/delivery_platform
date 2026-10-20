<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject a provider's deletion request: mandatory reason.
 */
class RejectProviderDeletionRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'processDeletion';
    }
}
