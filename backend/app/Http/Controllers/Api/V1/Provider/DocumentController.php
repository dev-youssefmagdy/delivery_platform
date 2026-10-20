<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreDocumentRequest;
use App\Http\Resources\Api\V1\Provider\DocumentResource;
use App\Models\OwnedDocument;
use App\Services\Provider\DocumentAccessService;
use App\Services\Provider\DocumentService;
use App\Services\Provider\ProviderOnboardingService;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The driver's own documents (provider and vehicle documents). Another provider's document is `403`; the file is only
 * reachable through a signed URL that expires after 5 minutes and still requires the owner's token.
 */
final class DocumentController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly DocumentService $documents,
        private readonly DocumentAccessService $access,
        private readonly ProviderOnboardingService $onboarding,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request));

        return ApiResponse::success(data: DocumentResource::collection($profile === null ? collect() : $this->documents->listFor($profile)));
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request)) ?? throw new AuthorizationException;
        $document = $this->documents->upload($profile, $request->toDto());

        return ApiResponse::created(__('api.provider.document_uploaded'), new DocumentResource($document->load('documentType')));
    }

    public function show(Request $request, string $document): JsonResponse
    {
        return ApiResponse::success(data: new DocumentResource($this->owned($request, $document)));
    }

    public function file(Request $request, string $document): StreamedResponse
    {
        return $this->access->serveToOwner($this->owned($request, $document));
    }

    private function owned(Request $request, string $uuid): OwnedDocument
    {
        $document = $this->documents->findAny($uuid) ?? throw (new ModelNotFoundException)->setModel(OwnedDocument::class);
        Gate::forUser($this->authenticatedUser($request))->authorize('manageOwn', $document);

        return $document;
    }
}
