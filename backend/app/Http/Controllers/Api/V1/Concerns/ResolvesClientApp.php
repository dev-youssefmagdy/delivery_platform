<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Enums\Identity\ClientApp;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * Identifies the calling app from the Sanctum token ability (never from a client-supplied header on
 * authenticated routes).
 */
trait ResolvesClientApp
{
    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        return $user instanceof User ? $user : throw new AuthorizationException;
    }

    private function clientApp(Request $request): ClientApp
    {
        return ClientApp::fromToken($this->authenticatedUser($request)->currentAccessToken()) ?? throw new AuthorizationException;
    }
}
