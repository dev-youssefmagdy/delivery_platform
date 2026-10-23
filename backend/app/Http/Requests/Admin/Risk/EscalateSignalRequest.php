<?php

namespace App\Http\Requests\Admin\Risk;

/** Risk signal action `escalate` (reason mandatory). */
class EscalateSignalRequest extends RiskSignalActionRequest
{
    protected function ability(): string
    {
        return 'escalate';
    }
}
