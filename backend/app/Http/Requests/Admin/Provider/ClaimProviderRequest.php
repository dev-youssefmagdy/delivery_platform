<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Claim an application in the verification queue for the signed-in officer.
 */
class ClaimProviderRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'claim';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
