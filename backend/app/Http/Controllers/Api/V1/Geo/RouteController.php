<?php

namespace App\Http\Controllers\Api\V1\Geo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Geo\EstimateRouteRequest;
use App\Http\Resources\Api\V1\Geo\RouteEstimateResource;
use App\Services\Geography\RouteEstimateService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /routes/estimate: distance, duration, polyline and, when a service category is given, the price guidance.
 */
final class RouteController extends Controller
{
    public function __construct(private readonly RouteEstimateService $routes) {}

    public function estimate(EstimateRouteRequest $request): JsonResponse
    {
        $estimate = $this->routes->estimate($request->origin(), $request->destination(), $request->stops(), $request->serviceCategoryId(), $request->serviceDetails());

        return ApiResponse::success(data: new RouteEstimateResource($estimate));
    }
}
