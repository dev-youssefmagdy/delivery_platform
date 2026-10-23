<?php

namespace App\Http\Requests\Admin\Reviews;

use App\Http\Requests\Admin\ModelActionRequest;

/** Close one report without touching the review (reason mandatory). */
class DismissReportRequest extends ModelActionRequest
{
    protected function parameter(): string
    {
        return 'report';
    }

    protected function ability(): string
    {
        return 'dismiss';
    }
}
