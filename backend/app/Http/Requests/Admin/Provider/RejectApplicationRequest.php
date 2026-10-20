<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject an application: mandatory reason shown to the provider.
 */
class RejectApplicationRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reject';
    }
}
