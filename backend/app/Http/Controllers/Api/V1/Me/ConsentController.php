<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Me\AcceptConsentRequest;
use App\Http\Resources\Api\V1\Identity\ConsentStatusResource;
use App\Services\Identity\ConsentService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ConsentController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly ConsentService $consents) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: new ConsentStatusResource(
            $this->consents->status($this->authenticatedUser($request), $this->clientApp($request)),
        ));
    }

    public function store(AcceptConsentRequest $request): JsonResponse
    {
        $status = $this->consents->accept($this->authenticatedUser($request), $this->clientApp($request), $request->toDto());

        return ApiResponse::success(__('api.me.consent_saved'), new ConsentStatusResource($status));
    }
}
