<?php

namespace App\Http\Requests\Admin\Risk;

/** Risk signal action `dismiss` (reason mandatory). */
class DismissSignalRequest extends RiskSignalActionRequest
{
    protected function ability(): string
    {
        return 'dismiss';
    }
}
