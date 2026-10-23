<?php

namespace App\Http\Requests\Admin\Safety;

/** Reopen a resolved case (reason mandatory). */
class ReopenIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'reopen';
    }

    protected function reasonIsRequired(): bool
    {
        return true;
    }

}
