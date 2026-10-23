<?php

namespace App\Http\Controllers\Api\V1\Growth;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Services\Growth\GrowthViewService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The customer's promotional credits (referral and loyalty rewards) with their expiry. Behind the `referrals` flag.
 */
final class CreditController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly GrowthViewService $views) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: $this->views->credits($this->authenticatedUser($request)));
    }
}
