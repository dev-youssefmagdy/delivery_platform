<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Notification\NotificationResource;
use App\Services\Notification\NotificationInboxService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * /me/notifications: the in-app inbox of the app the caller is signed in to (customer or driver token). Status-safe: a
 * suspended account can still read why. Reads are scoped to the owner, so a foreign id answers 404.
 */
final class NotificationController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly NotificationInboxService $inbox) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $page = $this->inbox->page($user, $app, $request->integer('per_page') ?: (int) config('notifications.inbox_page_size', 20), $request->boolean('unread'));

        return ApiResponse::paginated($page, NotificationResource::class, meta: ['unread_count' => $this->inbox->unreadCount($user, $app)]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $read = $this->inbox->markRead($user, $app, $notification);

        return ApiResponse::success(data: new NotificationResource($read), meta: ['unread_count' => $this->inbox->unreadCount($user, $app)]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $this->inbox->markAllRead($user, $app);

        return ApiResponse::success(meta: ['unread_count' => 0]);
    }
}
