<?php

namespace App\Http\Requests\Admin\Reviews;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Base of the review moderation actions (hide, restore, mark under review): the ability is checked on the bound `rating`, the reason is
 * mandatory and is stored with the moderation action and the audit entry. The request has no field for the review text on purpose.
 */
abstract class ReviewActionRequest extends ModelActionRequest
{
    protected function parameter(): string
    {
        return 'rating';
    }
}
