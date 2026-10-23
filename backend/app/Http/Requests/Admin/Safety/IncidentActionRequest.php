<?php

namespace App\Http\Requests\Admin\Safety;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Base of the Safety Center actions: the policy ability is checked on the bound `incident`; subclasses name the ability and add fields.
 */
abstract class IncidentActionRequest extends ModelActionRequest
{
    protected function parameter(): string
    {
        return 'incident';
    }
}
