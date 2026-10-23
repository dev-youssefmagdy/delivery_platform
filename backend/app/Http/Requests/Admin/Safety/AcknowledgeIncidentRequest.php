<?php

namespace App\Http\Requests\Admin\Safety;

/** Acknowledge a new case (takes it when unassigned). */
class AcknowledgeIncidentRequest extends IncidentActionRequest
{
    protected function ability(): string
    {
        return 'acknowledge';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
