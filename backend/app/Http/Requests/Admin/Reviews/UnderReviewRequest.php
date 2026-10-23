<?php

namespace App\Http\Requests\Admin\Reviews;

/** Review moderation action `markUnderReview` (reason mandatory). */
class UnderReviewRequest extends ReviewActionRequest
{
    protected function ability(): string
    {
        return 'markUnderReview';
    }
}
