<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reveal the provider's contact details (needs drivers.view_pii; every reveal is audited).
 */
class RevealProviderContactRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'viewPii';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
