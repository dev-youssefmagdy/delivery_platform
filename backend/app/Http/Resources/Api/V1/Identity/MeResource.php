<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\DTOs\Identity\MeData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * The signed-in user as the mobile apps see it. Only ever built for the authenticated user.
 *
 * @property MeData $resource
 */
class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource->user;
        $profile = $user->relationLoaded('customerProfile') ? $user->customerProfile : null;
        $deletion = $this->resource->deletionRequest;

        return [
            'id' => $user->uuid,
            'app' => $this->resource->app->value,
            'name' => $user->name,
            'phone' => $user->phone_e164,
            'phone_verified' => $user->phone_verified_at !== null,
            'email' => $user->email,
            'photo_url' => $this->photoUrl(),
            'preferred_locale' => $user->preferred_locale,
            'status' => $user->status->value,
            'profile_complete' => $user->isProfileComplete(),
            'created_at' => $user->created_at->toIso8601ZuluString(),
            'customer_profile' => $profile === null ? null : [
                'accessibility_needs' => $profile->accessibility_needs,
                'emergency_contact_name' => $profile->emergency_contact_name,
                'emergency_contact_phone' => $profile->emergency_contact_phone,
                'marketing_opt_in' => $profile->marketing_opt_in,
            ],
            'consents' => [
                'pending' => array_map(static fn ($policy): array => [
                    'id' => $policy->uuid,
                    'type' => $policy->type->value,
                    'version' => $policy->version,
                ], $this->resource->pendingConsents),
            ],
            'deletion_request' => $deletion === null ? null : [
                'id' => $deletion->uuid,
                'status' => $deletion->status->value,
                'requested_at' => $deletion->requested_at->toIso8601ZuluString(),
            ],
        ];
    }

    private function photoUrl(): ?string
    {
        $user = $this->resource->user;

        if ($user->photo_path === null) {
            return null;
        }

        return URL::temporarySignedRoute(
            'api.v1.media.user-photo',
            now()->addMinutes((int) config('identity.photo.url_ttl_minutes', 15)),
            ['user' => $user->uuid, 'v' => substr(sha1($user->photo_path), 0, 12)],
        );
    }
}
