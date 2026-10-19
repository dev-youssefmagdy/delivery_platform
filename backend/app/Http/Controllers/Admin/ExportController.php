<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminExport;
use App\Services\Admin\AdminExportService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Queued export status (polled by the listing) and signed, owner-only download.
 */
final class ExportController extends AdminController
{
    public function __construct(private readonly AdminExportService $exports) {}

    public function show(AdminExport $export): JsonResponse
    {
        return ApiResponse::success(data: $this->exports->status($export, $this->actor()));
    }

    public function download(AdminExport $export): StreamedResponse
    {
        [$disk, $path, $filename] = $this->exports->downloadable($export, $this->actor());

        return Storage::disk($disk)->download($path, $filename);
    }
}
