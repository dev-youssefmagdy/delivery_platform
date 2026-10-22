<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\DTOs\Support\ChatPage;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Support\MarkChatReadRequest;
use App\Http\Requests\Api\V1\Support\SendChatMessageRequest;
use App\Http\Resources\Api\V1\Support\ChatMessageResource;
use App\Services\Support\BookingChatService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Booking chat for both apps (SRS §14.2): GET/POST /bookings/{booking}/messages (customer) and /provider/bookings/{booking}/messages
 * (driver). The service decides participation and the window from the token and the booking; a non-participant gets 403.
 */
final class BookingChatController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly BookingChatService $chat) {}

    public function index(Request $request, string $booking): JsonResponse
    {
        $page = $this->chat->list(
            $this->authenticatedUser($request),
            $this->clientApp($request),
            $booking,
            $request->filled('after') ? (string) $request->query('after') : null,
            $request->filled('before') ? (string) $request->query('before') : null,
        );

        return ApiResponse::success(data: $this->items($page), meta: $this->meta($page));
    }

    public function store(SendChatMessageRequest $request, string $booking): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $message = $this->chat->send($user, $app, $booking, $request->toDto());
        $page = $this->chat->list($user, $app, $booking, null, null);

        return ApiResponse::created(__('api.chat.sent'), (new ChatMessageResource($message, $page->role, $booking))->resolve(), $this->meta($page));
    }

    public function read(MarkChatReadRequest $request, string $booking): JsonResponse
    {
        $changed = $this->chat->markRead($this->authenticatedUser($request), $this->clientApp($request), $booking, $request->filled('up_to') ? (string) $request->input('up_to') : null);

        return ApiResponse::success(meta: ['marked_read' => $changed]);
    }

    public function attachment(Request $request, string $booking, string $message): StreamedResponse
    {
        return $this->chat->attachmentOf($this->authenticatedUser($request), $this->clientApp($request), $booking, $message);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(ChatPage $page): array
    {
        return $page->messages->map(fn ($message): array => (new ChatMessageResource($message, $page->role, $page->bookingUuid))->resolve())->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(ChatPage $page): array
    {
        return [
            'role' => $page->role->value,
            'can_send' => $page->canSend,
            'closes_at' => $page->closesAt?->toIso8601ZuluString(),
            'unread' => $page->unread,
            'has_more' => $page->hasMore,
            'max_chars' => (int) config('support.chat.max_chars', 1000),
        ];
    }
}
