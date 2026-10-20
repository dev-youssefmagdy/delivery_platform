<?php

namespace App\Http\Requests\Admin\Provider;

use App\Http\Requests\Admin\ModelActionRequest;

/**
 * Ask for a fresh signed URL to view or download a document (needs documents.view).
 */
class IssueDocumentUrlRequest extends ModelActionRequest
{
    protected function ability(): string
    {
        return 'view';
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
