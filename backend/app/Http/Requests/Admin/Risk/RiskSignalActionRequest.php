<?php

namespace App\Http\Requests\Admin\Risk;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Base of the risk signal actions (suspend, restrict, dismiss, escalate): the ability is checked on the bound `signal`, the reason is
 * mandatory and goes to the audit trail.
 */
abstract class RiskSignalActionRequest extends ModelActionRequest
{
    protected function parameter(): string
    {
        return 'signal';
    }
}
