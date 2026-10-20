<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Deactivate a document type: mandatory reason.
 */
class DeactivateDocumentTypeRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'deactivate';
    }

    protected function parameter(): string
    {
        return 'documentType';
    }
}
