<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Approve a pending document.
 */
class ApproveDocumentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'approve';
    }

    protected function parameter(): string
    {
        return 'document';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
