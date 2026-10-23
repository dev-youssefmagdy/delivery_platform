<?php

namespace App\Http\Requests\Admin\Risk;

/** Risk signal action `restrict` (reason mandatory). */
class RestrictSignalSubjectRequest extends RiskSignalActionRequest
{
    protected function ability(): string
    {
        return 'restrict';
    }
}
