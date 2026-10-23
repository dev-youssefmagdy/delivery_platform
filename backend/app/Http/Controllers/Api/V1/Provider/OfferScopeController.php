<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Api\V1\Concerns\ResolvesProviderProfile;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\Provider\VehicleRepositoryInterface;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the scope builder of the provider app needs (P4.3): the JSON Schema of `config/offer_scope.php` (the same one the server
 * validates a counter-offer with), its version and the vehicle class of the vehicle the provider offers with (the scope must
 * name it, otherwise `offer_scope_vehicle_mismatch`). Nothing personal.
 */
final class OfferScopeController extends Controller
{
    use ResolvesClientApp;
    use ResolvesProviderProfile;

    public function __construct(private readonly VehicleRepositoryInterface $vehicles) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $this->providerProfile($request);
        $vehicle = $profile->current_vehicle_id === null ? null : $this->vehicles->find($profile->current_vehicle_id);
        $vehicle?->loadMissing('vehicleCategory');

        return ApiResponse::success(data: [
            'version' => (int) config('offer_scope.version', 1),
            'vehicle_class' => $vehicle?->vehicleCategory?->code,
            'schema' => config('offer_scope.schema'),
        ]);
    }
}
