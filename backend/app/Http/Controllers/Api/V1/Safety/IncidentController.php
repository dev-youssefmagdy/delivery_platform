<?php

namespace App\Http\Controllers\Api\V1\Safety;

use App\Http\Controllers\Api\V1\Concerns\ResolvesClientApp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Safety\IncidentAttachmentRequest;
use App\Http\Requests\Api\V1\Safety\IncidentUpdateRequest;
use App\Http\Requests\Api\V1\Safety\ReportIncidentRequest;
use App\Http\Resources\Api\V1\Safety\IncidentResource;
use App\Models\SafetyIncident;
use App\Services\Safety\IncidentFollowUpService;
use App\Services\Safety\IncidentReportingService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Safety cases of the signed-in person in either app (SRS §13.2, §20.5). Reachable by an account in any standing: a restricted
 * account must still be able to ask for help. Everything is scoped to the person AND the app; another person's case is a 404.
 */
final class IncidentController extends Controller
{
    use ResolvesClientApp;

    public function __construct(
        private readonly IncidentReportingService $reporting,
        private readonly IncidentFollowUpService $followUp,
    ) {}

    public function store(ReportIncidentRequest $request): JsonResponse
    {
        $result = $this->reporting->report($this->authenticatedUser($request), $this->clientApp($request), $request->toDto());
        $incident = $result->incident->load('booking:id,uuid,code');

        return ApiResponse::created(
            __($result->incident->trigger->value === 'sos' ? 'safety.api.sos_received' : 'safety.api.report_received'),
            $this->detail($incident, $result->actions) + ['joined_existing' => $result->joinedExisting],
        );
    }

    public function index(Request $request): JsonResponse
    {
        $page = $this->followUp->queryFor($this->authenticatedUser($request), $this->clientApp($request))->paginate(min(max($request->integer('per_page', 20), 1), 50));

        return ApiResponse::paginated($page, IncidentResource::class);
    }

    public function show(Request $request, string $incident): JsonResponse
    {
        $found = $this->followUp->find($this->authenticatedUser($request), $this->clientApp($request), $incident)->load('booking:id,uuid,code');

        return ApiResponse::success(data: $this->detail($found, $this->reporting->actionsOf($found)));
    }

    public function update(IncidentUpdateRequest $request, string $incident): JsonResponse
    {
        $found = $this->followUp->addUpdate($this->authenticatedUser($request), $this->clientApp($request), $incident, (string) $request->input('body'))->load('booking:id,uuid,code');

        return ApiResponse::created(__('safety.api.update_received'), $this->detail($found, $this->reporting->actionsOf($found)));
    }

    public function storeAttachment(IncidentAttachmentRequest $request, string $incident): JsonResponse
    {
        $file = $request->file('file');
        $attachment = $this->followUp->addAttachment($this->authenticatedUser($request), $this->clientApp($request), $incident, $file);

        return ApiResponse::created(__('safety.api.attachment_received'), ['id' => $attachment->uuid, 'mime' => $attachment->mime, 'size_bytes' => $attachment->size_bytes]);
    }

    public function attachmentFile(Request $request, string $incident, string $attachment): StreamedResponse
    {
        return $this->followUp->streamOwn($this->authenticatedUser($request), $this->clientApp($request), $incident, $attachment);
    }

    /**
     * @param list<array{code: string, status: string, message: string}> $actions
     * @return array<string, mixed>
     */
    private function detail(SafetyIncident $incident, array $actions): array
    {
        return (new IncidentResource($incident, $this->followUp->publicTimeline($incident), $this->followUp->contactActions($incident), $this->followUp->attachments($incident), $actions))->resolve();
    }
}
