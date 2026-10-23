<?php

namespace App\Http\Requests\Admin\Reviews;

/** Review moderation action `hide` (reason mandatory). */
class HideReviewRequest extends ReviewActionRequest
{
    protected function ability(): string
    {
        return 'hide';
    }
}
