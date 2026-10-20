<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Me\RegisterDeviceRequest;
use App\Http\Resources\Api\V1\Identity\DeviceResource;
use App\Services\Identity\DeviceService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class DeviceController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly DeviceService $devices) {}

    public function store(RegisterDeviceRequest $request): JsonResponse
    {
        $device = $this->devices->register($this->authenticatedUser($request), $this->clientApp($request), $request->toDto());

        return ApiResponse::success(__('api.me.device_saved'), new DeviceResource($device));
    }
}
