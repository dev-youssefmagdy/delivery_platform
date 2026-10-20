<?php

namespace App\Http\Controllers\Admin\Provider;

use App\Http\Controllers\Admin\AdminController;
use App\Models\OwnedDocument;
use App\Models\ProviderDocument;
use App\Models\VehicleDocument;
use App\Models\VehiclePhoto;
use App\Services\Provider\DocumentAccessService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private files to admins. The routes are signed (5 minutes) AND bound to the admin the URL was issued to, and
 * each also passes its policy check; every document view or download is audited by `DocumentAccessService`.
 */
final class ProviderFileController extends AdminController
{
    public function __construct(private readonly DocumentAccessService $access) {}

    public function providerDocument(Request $request, ProviderDocument $document): StreamedResponse
    {
        return $this->serve($request, $document);
    }

    public function vehicleDocument(Request $request, VehicleDocument $document): StreamedResponse
    {
        return $this->serve($request, $document);
    }

    public function vehiclePhoto(Request $request, VehiclePhoto $photo): StreamedResponse
    {
        $this->assertIssuedToThisAdmin($request);

        return $this->access->serveVehiclePhoto($photo);
    }

    public function vehiclePhotoUrl(VehiclePhoto $photo): JsonResponse
    {
        return ApiResponse::success(data: [
            'url' => $this->access->adminPhotoUrl($photo, $this->actor()),
            'mime' => $photo->mime,
            'expires_in' => (int) config('provider.documents.url_ttl_minutes', 5) * 60,
        ]);
    }

    private function serve(Request $request, OwnedDocument $document): StreamedResponse
    {
        $this->assertIssuedToThisAdmin($request);
        $mode = $request->query('mode') === DocumentAccessService::MODE_DOWNLOAD ? DocumentAccessService::MODE_DOWNLOAD : DocumentAccessService::MODE_VIEW;

        return $this->access->serveToAdmin($document, $this->actor(), $mode);
    }

    private function assertIssuedToThisAdmin(Request $request): void
    {
        abort_unless((int) $request->query('admin') === $this->actor()->id, 403);
    }
}
