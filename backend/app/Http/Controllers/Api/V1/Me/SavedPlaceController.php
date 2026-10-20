<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Geo\SavedPlaceRequest;
use App\Http\Resources\Api\V1\Geo\SavedPlaceResource;
use App\Services\Geography\SavedPlaceService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /me/saved-places. The route parameter is the place uuid; ownership is part of every query, so a foreign
 * uuid answers 404 exactly like a missing one.
 */
final class SavedPlaceController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly SavedPlaceService $places) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: SavedPlaceResource::collection($this->places->list($this->authenticatedUser($request))));
    }

    public function store(SavedPlaceRequest $request): JsonResponse
    {
        $place = $this->places->create($this->authenticatedUser($request), $request->toDto());

        return ApiResponse::created(data: new SavedPlaceResource($place));
    }

    public function update(SavedPlaceRequest $request, string $place): JsonResponse
    {
        return ApiResponse::success(data: new SavedPlaceResource($this->places->update($this->authenticatedUser($request), $place, $request->toDto())));
    }

    public function destroy(Request $request, string $place): JsonResponse
    {
        $this->places->delete($this->authenticatedUser($request), $place);

        return ApiResponse::success();
    }
}
