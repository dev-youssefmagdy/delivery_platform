<?php

namespace App\Http\Controllers\Admin\Provider;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Provider\ApproveDocumentRequest;
use App\Http\Requests\Admin\Provider\IssueDocumentUrlRequest;
use App\Http\Requests\Admin\Provider\RejectDocumentRequest;
use App\Models\Admin;
use App\Models\ProviderDocument;
use App\Services\Provider\DocumentAccessService;
use App\Services\Provider\DocumentService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Http\JsonResponse;

/**
 * Admin actions on a provider's own documents: signed URL (view / download, audited when the file is served), approve,
 * reject (reason).
 */
final class ProviderDocumentController extends AdminController
{
    use HandlesDocumentActions;

    public function __construct(
        private readonly DocumentService $documents,
        private readonly DocumentAccessService $access,
    ) {}

    public function url(IssueDocumentUrlRequest $request, ProviderDocument $document): JsonResponse
    {
        return $this->issueUrl($request, $document);
    }

    public function approve(ApproveDocumentRequest $request, ProviderDocument $document): AdminFormResponse
    {
        return $this->approveDocument($document, $request->boolean('reload'));
    }

    public function reject(RejectDocumentRequest $request, ProviderDocument $document): AdminFormResponse
    {
        return $this->rejectDocument($document, (string) $request->reason(), $request->boolean('reload'));
    }

    protected function documentService(): DocumentService
    {
        return $this->documents;
    }

    protected function documentAccess(): DocumentAccessService
    {
        return $this->access;
    }

    protected function actingAdmin(): Admin
    {
        return $this->actor();
    }

    protected function respond(?string $message): AdminFormResponse
    {
        return $this->formResponse($message);
    }
}
