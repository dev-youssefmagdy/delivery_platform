<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Provider\ProviderDocumentsDataTable;
use App\DataTables\Admin\Provider\VehicleDocumentsDataTable;
use App\Models\Admin;
use App\Models\OwnedDocument;
use App\Models\VehicleDocument;
use App\Services\Provider\DocumentAccessService;
use App\Services\Provider\DocumentService;
use App\Support\Http\AdminFormResponse;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The three document actions shared by the provider-document and vehicle-document controllers: issue a signed URL,
 * approve and reject. Authorization is done by the FormRequests (policy abilities on the bound document).
 */
trait HandlesDocumentActions
{
    abstract protected function documentService(): DocumentService;

    abstract protected function documentAccess(): DocumentAccessService;

    abstract protected function actingAdmin(): Admin;

    abstract protected function respond(?string $message): AdminFormResponse;

    protected function issueUrl(Request $request, OwnedDocument $document): JsonResponse
    {
        $mode = $request->input('mode') === DocumentAccessService::MODE_DOWNLOAD ? DocumentAccessService::MODE_DOWNLOAD : DocumentAccessService::MODE_VIEW;

        return ApiResponse::success(data: [
            'url' => $this->documentAccess()->adminUrl($document, $this->actingAdmin(), $mode),
            'mime' => $document->mime,
            'expires_in' => (int) config('provider.documents.url_ttl_minutes', 5) * 60,
        ]);
    }

    protected function approveDocument(OwnedDocument $document, bool $reloadPage): AdminFormResponse
    {
        $this->documentService()->approve($document, $this->actingAdmin());

        return $this->afterDecision(__('admin.documents.approved'), $document, $reloadPage);
    }

    protected function rejectDocument(OwnedDocument $document, string $reason, bool $reloadPage): AdminFormResponse
    {
        $this->documentService()->reject($document, $reason, $this->actingAdmin());

        return $this->afterDecision(__('admin.documents.rejected'), $document, $reloadPage);
    }

    private function afterDecision(string $message, OwnedDocument $document, bool $reloadPage): AdminFormResponse
    {
        $response = $this->respond($message)->reloadTable($this->tableId($document));

        return $reloadPage ? $response->reload() : $response;
    }

    private function tableId(OwnedDocument $document): string
    {
        return $document instanceof VehicleDocument ? VehicleDocumentsDataTable::TABLE_ID : ProviderDocumentsDataTable::TABLE_ID;
    }
}
