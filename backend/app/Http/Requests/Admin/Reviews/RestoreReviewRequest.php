<?php

namespace App\Http\Requests\Admin\Reviews;

/** Review moderation action `restore` (reason mandatory). */
class RestoreReviewRequest extends ReviewActionRequest
{
    protected function ability(): string
    {
        return 'restore';
    }
}
