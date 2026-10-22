<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Me\UpdateNotificationPreferencesRequest;
use App\Http\Resources\Api\V1\Notification\NotificationPreferencesResource;
use App\Services\Notification\NotificationPreferenceService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /me/notification-preferences (SRS §14.1): what the person receives per category and channel. Essential categories keep
 * push and the inbox on; the response says which channels are locked so the app can explain why.
 */
final class NotificationPreferenceController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly NotificationPreferenceService $preferences) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(data: new NotificationPreferencesResource($this->preferences->matrix($this->authenticatedUser($request))));
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->preferences->update($user, $request->toDtos());

        return ApiResponse::success(__('api.me.preferences_saved'), new NotificationPreferencesResource($this->preferences->matrix($user)));
    }
}
