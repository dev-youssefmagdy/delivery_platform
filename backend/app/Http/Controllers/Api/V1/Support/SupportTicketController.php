<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Enums\Support\TicketCategory;
use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Support\AppealRequest;
use App\Http\Requests\Api\V1\Support\OpenTicketRequest;
use App\Http\Requests\Api\V1\Support\ReplyTicketRequest;
use App\Http\Resources\Api\V1\Support\TicketResource;
use App\Services\Support\TicketService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Help center and tickets for both apps (SRS §14.3). Everything is scoped to the signed-in person AND the app they wrote
 * from; another person's ticket is a 404. Internal notes and staff-only fields are never part of a response.
 */
final class SupportTicketController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly TicketService $tickets) {}

    public function categories(Request $request): JsonResponse
    {
        $app = $this->clientApp($request);

        return ApiResponse::success(data: array_map(static fn (TicketCategory $category): array => ['value' => $category->value, 'label' => $category->label()], TicketCategory::forApp($app->value)));
    }

    public function index(Request $request): JsonResponse
    {
        $status = in_array($request->query('status'), ['active', 'finished'], true) ? (string) $request->query('status') : null;
        $page = $this->tickets->queryFor($this->authenticatedUser($request), $this->clientApp($request), $status)->paginate(min(max($request->integer('per_page', 20), 1), 50));

        return ApiResponse::paginated($page, TicketResource::class);
    }

    public function store(OpenTicketRequest $request): JsonResponse
    {
        $ticket = $this->tickets->open($this->authenticatedUser($request), $this->clientApp($request), $request->toDto());

        return ApiResponse::created(__('api.support.ticket_created'), TicketResource::detailed($ticket->load('booking:id,uuid,code'), $this->tickets->publicThread($ticket)));
    }

    public function show(Request $request, string $ticket): JsonResponse
    {
        $found = $this->tickets->findFor($this->authenticatedUser($request), $this->clientApp($request), $ticket)->load('booking:id,uuid,code');

        return ApiResponse::success(data: TicketResource::detailed($found, $this->tickets->publicThread($found)));
    }

    public function reply(ReplyTicketRequest $request, string $ticket): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $app = $this->clientApp($request);
        $this->tickets->reply($user, $app, $ticket, $request->toDto());
        $found = $this->tickets->findFor($user, $app, $ticket)->load('booking:id,uuid,code');

        return ApiResponse::created(__('api.support.reply_sent'), TicketResource::detailed($found, $this->tickets->publicThread($found)));
    }

    public function attachment(Request $request, string $ticket, string $attachment): StreamedResponse
    {
        return $this->tickets->attachmentFor($this->authenticatedUser($request), $this->clientApp($request), $ticket, $attachment);
    }

    public function appeal(AppealRequest $request, string $booking): JsonResponse
    {
        $ticket = $this->tickets->openAppeal($this->authenticatedUser($request), $this->clientApp($request), $booking, (string) $request->input('reason'));

        return ApiResponse::created(__('api.support.appeal_created'), TicketResource::detailed($ticket->load('booking:id,uuid,code'), $this->tickets->publicThread($ticket)));
    }
}
