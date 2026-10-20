<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Reject a document: mandatory reason shown to the provider.
 */
class RejectDocumentRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'reject';
    }

    protected function parameter(): string
    {
        return 'document';
    }
}
