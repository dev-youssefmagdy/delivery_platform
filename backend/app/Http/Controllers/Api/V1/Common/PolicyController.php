<?php

namespace App\Http\Controllers\Api\V1\Common;

use App\Enums\Policy\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Identity\PolicyResource;
use App\Services\Policy\PolicyReadService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public policy text (terms, privacy ...) for the consent screens: the current published version only.
 */
final class PolicyController extends Controller
{
    public function __construct(private readonly PolicyReadService $policies) {}

    public function show(string $type): JsonResponse
    {
        $policy = $this->policies->current(PolicyType::from($type));

        return ApiResponse::success(data: new PolicyResource($policy), headers: ['Vary' => 'Accept-Language']);
    }
}
