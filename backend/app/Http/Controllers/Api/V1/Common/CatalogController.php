<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\ListServiceCategoriesRequest;
use App\Http\Requests\Api\V1\Catalog\ListVehicleCategoriesRequest;
use App\Http\Resources\Api\V1\Catalog\ServiceCategoryResource;
use App\Http\Resources\Api\V1\Catalog\ServiceFamilyResource;
use App\Http\Resources\Api\V1\Catalog\VehicleCategoryResource;
use App\Services\Catalog\CatalogReadService;
use App\Support\Catalog\CatalogCache;
use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Public, cacheable catalog endpoints (SRS §20.2): only active entries that have a published version.
 */
final class CatalogController extends Controller
{
    public function __construct(private readonly CatalogReadService $catalog, private readonly CatalogCache $cache) {}

    public function families(): JsonResponse
    {
        return ApiResponse::success(data: $this->cached(
            'families.'.app()->getLocale(),
            fn (): string => ServiceFamilyResource::collection($this->catalog->families())->toJson(),
        ));
    }

    public function categories(ListServiceCategoriesRequest $request): JsonResponse
    {
        if ($request->point() === null) {
            return ApiResponse::success(data: $this->cached(
                'categories.'.app()->getLocale().'.'.($request->bookingType()->value ?? 'all').'.'.($request->familyId() ?? 'all'),
                fn (): string => ServiceCategoryResource::collection($this->catalog->categories($request->bookingType(), $request->familyId())->categories)->toJson(),
            ));
        }

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
        return ApiResponse::success(data: $this->cached(
            'vehicle-categories.'.app()->getLocale().'.'.($request->serviceCategoryId() ?? 'all'),
            fn (): string => VehicleCategoryResource::collection($this->catalog->vehicleCategories($request->serviceCategoryId()))->toJson(),
        ));
    }

    /**
     * Cached as the finished (localized) JSON text: `cache.serializable_classes` is false, so neither models nor the stdClass
     * objects of empty translation maps can live in the cache.
     *
     * @param Closure(): string $build
     */
    private function cached(string $name, Closure $build): mixed
    {
        return json_decode((string) $this->cache->remember($name, $build));
    }
}
