<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Me\RequestAccountDeletionRequest;
use App\Http\Resources\Api\V1\Identity\DeletionRequestResource;
use App\Services\Identity\AccountDeletionService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountDeletionController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly AccountDeletionService $deletions) {}

    public function store(RequestAccountDeletionRequest $request): JsonResponse
    {
        $deletion = $this->deletions->request($this->authenticatedUser($request), $request->validated('reason'));

        return ApiResponse::created(__('api.me.deletion_requested'), new DeletionRequestResource($deletion));
    }

    public function destroy(Request $request, string $deletionRequest): JsonResponse
    {
        $cancelled = $this->deletions->cancel($this->authenticatedUser($request), $deletionRequest);

        return ApiResponse::success(__('api.me.deletion_cancelled'), new DeletionRequestResource($cancelled));
    }
}
