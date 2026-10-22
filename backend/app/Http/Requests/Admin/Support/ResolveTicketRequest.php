<?php

namespace App\Http\Requests\Admin\Support;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Resolve a ticket (the reason is the resolution the person reads).
 */
class ResolveTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'resolve';
    }

    protected function parameter(): string
    {
        return 'ticket';
    }

    protected function reasonIsRequired(): bool
    {
        return true;
    }
}
