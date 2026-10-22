<?php

namespace App\Http\Requests\Admin\Support;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Activate or deactivate a macro (reason mandatory when deactivating is decided in the route; kept optional here).
 */
class ToggleMacroRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'toggle';
    }

    protected function parameter(): string
    {
        return 'macro';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
