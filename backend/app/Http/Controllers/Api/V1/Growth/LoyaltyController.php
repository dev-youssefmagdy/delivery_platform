<?php

namespace App\Http\Controllers\Api\V1\Growth;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Services\Growth\GrowthViewService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's loyalty status: completed jobs, level, next level. Behind the `loyalty` flag.
 */
final class LoyaltyController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly GrowthViewService $views) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(data: $this->views->loyaltyStatus($this->authenticatedUser($request)));
    }
}
