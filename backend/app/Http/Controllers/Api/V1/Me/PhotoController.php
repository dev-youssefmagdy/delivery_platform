<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Me\StorePhotoRequest;
use App\Http\Resources\Api\V1\Identity\MeResource;
use App\Services\Identity\ProfileService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PhotoController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly ProfileService $profiles) {}

    public function store(StorePhotoRequest $request): JsonResponse
    {
        $me = $this->profiles->replacePhoto($this->authenticatedUser($request), $this->clientApp($request), $request->file('photo'));

        return ApiResponse::success(__('api.me.photo_saved'), new MeResource($me));
    }
}
