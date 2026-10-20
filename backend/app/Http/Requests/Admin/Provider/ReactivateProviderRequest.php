<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Lift a temporary suspension: mandatory reason.
 */
class ReactivateProviderRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reactivate';
    }
}
