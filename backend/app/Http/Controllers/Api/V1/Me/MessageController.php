<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Identity\CustomerMessageResource;
use App\Services\Customers\CustomerInboxService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /me/messages: help messages support sent to the customer. Status-safe (a suspended customer can still read why).
 * Ownership is part of every query, so a foreign uuid answers 404 like a missing one.
 */
final class MessageController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly CustomerInboxService $inbox) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: CustomerMessageResource::collection($this->inbox->list($this->authenticatedUser($request))));
    }

    public function read(Request $request, string $message): JsonResponse
    {
        return ApiResponse::success(data: new CustomerMessageResource($this->inbox->markRead($this->authenticatedUser($request), $message)));
    }
}
