<?php

namespace App\Http\Controllers\Api\V1\Geo;

use App\DTOs\Geography\PlaceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Geo\ReverseGeocodeRequest;
use App\Http\Requests\Api\V1\Geo\SearchPlacesRequest;
use App\Http\Resources\Api\V1\Geo\PlaceResource;
use App\Services\Geography\MapsService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Place search and reverse geocoding for the booking map (authenticated apps only: they spend maps quota).
 */
final class PlaceController extends Controller
{
    public function __construct(private readonly MapsService $maps) {}

    public function search(SearchPlacesRequest $request): JsonResponse
    {
        $places = $this->maps->search((string) $request->validated('q'), $request->near(), $request->limit());

        return ApiResponse::success(data: PlaceResource::collection($places));
    }

    public function reverse(ReverseGeocodeRequest $request): JsonResponse
    {
        $place = $this->maps->reverse($request->point());

        return ApiResponse::success(data: $place instanceof PlaceData ? new PlaceResource($place) : null);
    }
}
