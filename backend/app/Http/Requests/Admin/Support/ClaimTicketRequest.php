<?php

namespace App\Http\Requests\Admin\Support;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Take a ticket.
 */
class ClaimTicketRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'claim';
    }

    protected function parameter(): string
    {
        return 'ticket';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
