<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\ListServiceCategoriesRequest;
use App\Http\Requests\Api\V1\Catalog\ListVehicleCategoriesRequest;
use App\Http\Resources\Api\V1\Catalog\ServiceCategoryResource;
use App\Http\Resources\Api\V1\Catalog\ServiceFamilyResource;
use App\Http\Resources\Api\V1\Catalog\VehicleCategoryResource;
use App\Services\Catalog\CatalogReadService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public, cacheable catalog endpoints (SRS §20.2): only active entries that have a published version.
 */
final class CatalogController extends Controller
{
    public function __construct(private readonly CatalogReadService $catalog) {}

    public function families(): JsonResponse
    {
        return ApiResponse::success(data: ServiceFamilyResource::collection($this->catalog->families()));
    }

    public function categories(ListServiceCategoriesRequest $request): JsonResponse
    {
        $listing = $this->catalog->categories($request->bookingType(), $request->familyId(), $request->point());

        return ApiResponse::success(
            data: ServiceCategoryResource::collection($listing->categories),
            meta: $listing->area === null ? [] : ['service_area' => [
                'in_service_area' => $listing->area->inServiceArea(),
                'zone_id' => $listing->area->zone?->id,
                'city_id' => $listing->area->city?->id,
                'restricted_pickup' => $listing->area->restrictedPickup(),
            ]],
        );
    }

    public function vehicleCategories(ListVehicleCategoriesRequest $request): JsonResponse
    {
        return ApiResponse::success(data: VehicleCategoryResource::collection($this->catalog->vehicleCategories($request->serviceCategoryId())));
    }
}
