<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Activate a document type.
 */
class ActivateDocumentTypeRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'activate';
    }

    protected function parameter(): string
    {
        return 'documentType';
    }

    protected function reasonIsRequired(): bool
    {
        return false;
    }
}
