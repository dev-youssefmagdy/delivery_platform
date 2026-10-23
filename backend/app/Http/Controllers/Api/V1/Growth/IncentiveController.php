<?php

namespace App\Http\Controllers\Api\V1\Growth;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Services\Growth\GrowthViewService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Open incentive campaigns of the signed-in provider with their own quest progress. Behind the `incentives` flag.
 */
final class IncentiveController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly GrowthViewService $views) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: $this->views->incentives($this->authenticatedUser($request)));
    }
}
