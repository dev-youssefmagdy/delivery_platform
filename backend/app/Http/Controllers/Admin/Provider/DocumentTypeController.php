<?php

namespace App\Http\Controllers\Admin\Provider;

use App\DataTables\Admin\Provider\DocumentRequirementsDataTable;
use App\DataTables\Admin\Provider\DocumentTypesDataTable;
use App\DTOs\Provider\DocumentTypeData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Provider\ActivateDocumentTypeRequest;
use App\Http\Requests\Admin\Provider\DeactivateDocumentTypeRequest;
use App\Http\Requests\Admin\Provider\DestroyDocumentRequirementRequest;
use App\Http\Requests\Admin\Provider\DestroyDocumentTypeRequest;
use App\Http\Requests\Admin\Provider\StoreDocumentRequirementRequest;
use App\Http\Requests\Admin\Provider\StoreDocumentTypeRequest;
use App\Http\Requests\Admin\Provider\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use App\Services\Audit\AuditTrailService;
use App\Services\Provider\DocumentTypeService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Document types and requirements (documents.manage_types): what providers and vehicles must upload, and where it is
 * mandatory (country, city, service, vehicle category, provider type).
 */
final class DocumentTypeController extends AdminController
{
    public function __construct(private readonly DocumentTypeService $service) {}

    public function index(DocumentTypesDataTable $table): mixed
    {
        return $table->render('admin.document-types.index');
    }

    public function create(): View
    {
        return view('admin.document-types.create');
    }

    public function store(StoreDocumentTypeRequest $request): AdminFormResponse
    {
        $type = $this->service->create(DocumentTypeData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.document_types.created'))->redirect(route('admin.document-types.show', $type));
    }

    public function show(DocumentType $documentType): View
    {
        return view('admin.document-types.show', ['type' => $documentType->loadCount('requirements')]);
    }

    public function activity(DocumentType $documentType, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($documentType)]);
    }

    public function requirements(DocumentRequirementsDataTable $table, DocumentType $documentType): mixed
    {
        return $table->render('admin.document-types.tabs.requirements', ['type' => $documentType]);
    }

    public function edit(DocumentType $documentType): View
    {
        return view('admin.document-types.edit', ['type' => $documentType]);
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): AdminFormResponse
    {
        $this->service->update($documentType, DocumentTypeData::fromRequest($request), $this->actor());

        return $this->formResponse(__('admin.document_types.updated'))->redirect(route('admin.document-types.show', $documentType));
    }

    public function activate(ActivateDocumentTypeRequest $request, DocumentType $documentType): AdminFormResponse
    {
        $this->service->setActive($documentType, true, null, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.activated'), route('admin.document-types.show', $documentType));
    }

    public function deactivate(DeactivateDocumentTypeRequest $request, DocumentType $documentType): AdminFormResponse
    {
        $this->service->setActive($documentType, false, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.catalog.deactivated'), route('admin.document-types.show', $documentType));
    }

    public function destroy(DestroyDocumentTypeRequest $request, DocumentType $documentType): AdminFormResponse
    {
        $this->service->delete($documentType, (string) $request->reason(), $this->actor());

        $response = $this->formResponse(__('admin.document_types.deleted'))->reloadTable(DocumentTypesDataTable::TABLE_ID);

        return $request->boolean('from_show') ? $response->redirect(route('admin.document-types.index')) : $response;
    }

    public function storeRequirement(StoreDocumentRequirementRequest $request, DocumentType $documentType): AdminFormResponse
    {
        $this->service->addRequirement($documentType, $request->toDto(), (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.document_types.requirement_added'))->closeModal()->reset()->reloadTable(DocumentRequirementsDataTable::TABLE_ID);
    }

    public function destroyRequirement(DestroyDocumentRequirementRequest $request, DocumentType $documentType, int $requirement): AdminFormResponse
    {
        $this->service->removeRequirement($documentType, $requirement, (string) $request->reason(), $this->actor());

        return $this->formResponse(__('admin.document_types.requirement_removed'))->reloadTable(DocumentRequirementsDataTable::TABLE_ID);
    }

    private function afterStateChange(bool $fromShow, string $message, string $showUrl): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(DocumentTypesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect($showUrl) : $response;
    }
}
