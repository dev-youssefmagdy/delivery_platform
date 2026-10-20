<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Remove a requirement from a document type: mandatory reason.
 */
class DestroyDocumentRequirementRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'update';
    }

    protected function parameter(): string
    {
        return 'documentType';
    }
}
