<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Delete a document type that was never used: mandatory reason.
 */
class DestroyDocumentTypeRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'delete';
    }

    protected function parameter(): string
    {
        return 'documentType';
    }
}
