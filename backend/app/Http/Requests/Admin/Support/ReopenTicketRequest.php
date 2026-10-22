<?php

namespace App\Http\Requests\Admin\Support;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reopen a resolved or closed ticket (reason mandatory).
 */
class ReopenTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reopen';
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
