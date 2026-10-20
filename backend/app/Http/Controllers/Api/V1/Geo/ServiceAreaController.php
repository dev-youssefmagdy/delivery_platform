<?php

namespace App\Http\Controllers\Api\V1\Geo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Geo\ResolveAreaRequest;
use App\Http\Resources\Api\V1\Geo\ResolvedAreaResource;
use App\Services\Geography\GeoService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /service-areas/resolve: public, so the apps can show the coverage state before sign-in.
 */
final class ServiceAreaController extends Controller
{
    public function __construct(private readonly GeoService $geo) {}

    public function resolve(ResolveAreaRequest $request): JsonResponse
    {
        return ApiResponse::success(data: new ResolvedAreaResource($this->geo->resolve($request->point())));
    }
}
