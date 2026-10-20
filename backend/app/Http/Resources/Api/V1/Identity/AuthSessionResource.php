<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\DTOs\Identity\AuthSession;
use App\DTOs\Identity\MeData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Response of verify/refresh: the bearer token (shown once) plus the user.
 *
 * @property array{session: AuthSession, me: MeData} $resource
 */
class AuthSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session = $this->resource['session'];

        return [
            'token' => $session->token,
            'token_type' => 'Bearer',
            'expires_at' => $session->expiresAt->format('Y-m-d\TH:i:s\Z'),
            'abilities' => [$session->app->ability()],
            'is_new_user' => $session->isNewUser,
            'user' => new MeResource($this->resource['me']),
        ];
    }
}
