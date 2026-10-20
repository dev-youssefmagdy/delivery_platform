<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Provider\StoreVehiclePhotoRequest;
use App\Http\Requests\Api\V1\Provider\StoreVehicleRequest;
use App\Http\Requests\Api\V1\Provider\UpdateVehicleRequest;
use App\Http\Resources\Api\V1\Provider\VehicleResource;
use App\Models\Vehicle;
use App\Models\VehiclePhoto;
use App\Services\Provider\DocumentAccessService;
use App\Services\Provider\ProviderOnboardingService;
use App\Services\Provider\VehicleService;
use App\Support\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The driver's own vehicles. Route binding is by uuid; another provider's vehicle is `403` (`VehiclePolicy::manageOwn`).
 */
final class VehicleController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly VehicleService $service,
        private readonly ProviderOnboardingService $onboarding,
        private readonly DocumentAccessService $access,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request));

        return ApiResponse::success(data: VehicleResource::collection($profile === null ? collect() : $this->service->listFor($profile)));
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $profile = $this->onboarding->profileOf($this->authenticatedUser($request)) ?? throw new AuthorizationException;
        $vehicle = $this->service->create($profile, $request->toDto());

        return ApiResponse::created(__('api.provider.vehicle_created'), new VehicleResource($vehicle));
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        return ApiResponse::success(data: new VehicleResource($this->owned($request, $vehicle)->load('photos')));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $updated = $this->service->update($this->owned($request, $vehicle), $request->toDto());

        return ApiResponse::success(__('api.provider.vehicle_updated'), new VehicleResource($updated));
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->service->delete($this->owned($request, $vehicle));

        return ApiResponse::success(__('api.provider.vehicle_deleted'));
    }

    public function storePhoto(StoreVehiclePhotoRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->service->addPhoto($this->owned($request, $vehicle), $request->toDto());

        return ApiResponse::created(__('api.provider.vehicle_photo_added'), new VehicleResource($vehicle->refresh()->load('photos')));
    }

    public function destroyPhoto(Request $request, Vehicle $vehicle, string $photo): JsonResponse
    {
        $this->service->removePhoto($this->owned($request, $vehicle), $photo);

        return ApiResponse::success(__('api.provider.vehicle_photo_removed'), new VehicleResource($vehicle->refresh()->load('photos')));
    }

    public function submitReview(Request $request, Vehicle $vehicle): JsonResponse
    {
        $submitted = $this->service->submitForReview($this->owned($request, $vehicle));

        return ApiResponse::success(__('api.provider.vehicle_submitted'), new VehicleResource($submitted));
    }

    public function photoFile(Request $request, string $photo): StreamedResponse
    {
        $model = $this->service->findPhoto($photo) ?? throw (new ModelNotFoundException)->setModel(VehiclePhoto::class);
        $this->owned($request, $model->vehicle);

        return $this->access->serveVehiclePhoto($model);
    }

    private function owned(Request $request, Vehicle $vehicle): Vehicle
    {
        Gate::forUser($this->authenticatedUser($request))->authorize('manageOwn', $vehicle);
        $vehicle->loadMissing('providerProfile');

        return $vehicle;
    }
}
