<?php

namespace App\Http\Controllers\Api\V1\Geo;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Geo\CityResource;
use App\Services\Geography\CityService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /cities: active cities (public reference data, cacheable).
 */
final class CityController extends Controller
{
    public function __construct(private readonly CityService $cities) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(data: CityResource::collection($this->cities->activeList()));
    }
}
