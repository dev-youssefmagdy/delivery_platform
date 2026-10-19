<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Admin\LookupQueryData;
use App\Http\Requests\Admin\LookupRequest;
use App\Services\Admin\LookupService;
use App\Support\Admin\AdminAccess;
use App\Support\Admin\Lookups\LookupRegistry;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Remote-select data source: GET /admin/lookups/{lookup}?q= (allowlisted, permission-checked, max 20).
 */
final class LookupController extends AdminController
{
    public function __construct(private readonly LookupService $lookups) {}

    public function __invoke(LookupRequest $request, string $lookup, LookupRegistry $registry): JsonResponse
    {
        $definition = $registry->get($lookup);

        $permission = $definition->permission();
        abort_if($permission !== null && ! AdminAccess::allows($permission->value), 403);

        return ApiResponse::success(data: $this->lookups->search($definition, LookupQueryData::fromRequest($request)));
    }
}
