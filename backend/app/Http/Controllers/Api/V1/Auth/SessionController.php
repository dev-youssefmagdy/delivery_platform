<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Identity\AuthSessionResource;
use App\Services\Identity\AuthService;
use App\Services\Identity\ProfileService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Token refresh (re-issue) and logout (revoke the presented token only).
 */
final class SessionController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly AuthService $auth,
        private readonly ProfileService $profiles,
    ) {}

    public function refresh(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $name = (string) $user->currentAccessToken()->name;

        $session = $this->auth->refresh($user, $app, $name);

        return ApiResponse::success(data: new AuthSessionResource([
            'session' => $session,
            'me' => $this->profiles->me($user, $app),
        ]));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($this->authenticatedUser($request));

        return ApiResponse::success(__('api.auth.logged_out'));
    }
}
