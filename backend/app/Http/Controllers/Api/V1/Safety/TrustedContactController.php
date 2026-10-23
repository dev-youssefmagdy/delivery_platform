<?php

namespace App\Http\Controllers\Api\V1\Safety;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Safety\TrustedContactRequest;
use App\Http\Resources\Api\V1\Safety\TrustedContactResource;
use App\Services\Safety\TrustedContactService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Trusted contacts of the signed-in person (shared by both apps of the same account). Only masked phones leave the server.
 */
final class TrustedContactController extends Controller
{
    use ResolvesClientApp;

    public function __construct(private readonly TrustedContactService $contacts) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(data: [
            'items' => TrustedContactResource::collection($this->contacts->list($this->authenticatedUser($request)))->resolve(),
            'max' => (int) config('safety.trusted_contacts.max_per_user', 5),
        ]);
    }

    public function store(TrustedContactRequest $request): JsonResponse
    {
        return ApiResponse::created(__('safety.api.contact_saved'), new TrustedContactResource($this->contacts->add($this->authenticatedUser($request), $request->toDto())));
    }

    public function update(TrustedContactRequest $request, string $contact): JsonResponse
    {
        return ApiResponse::success(__('safety.api.contact_saved'), new TrustedContactResource($this->contacts->update($this->authenticatedUser($request), $contact, $request->toDto())));
    }

    public function destroy(Request $request, string $contact): JsonResponse
    {
        $this->contacts->remove($this->authenticatedUser($request), $contact);

        return ApiResponse::success(__('safety.api.contact_removed'));
    }
}
