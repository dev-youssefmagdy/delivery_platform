<?php

namespace App\Http\Controllers\Admin\Safety;

use App\DataTables\Admin\Safety\SafetyIncidentsDataTable;
use App\DTOs\Safety\IncidentFilterData;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Safety\AcknowledgeIncidentRequest;
use App\Http\Requests\Admin\Safety\AssignIncidentRequest;
use App\Http\Requests\Admin\Safety\EscalateIncidentRequest;
use App\Http\Requests\Admin\Safety\LegalHoldRequest;
use App\Http\Requests\Admin\Safety\MessageIncidentRequest;
use App\Http\Requests\Admin\Safety\NoteIncidentRequest;
use App\Http\Requests\Admin\Safety\ReopenIncidentRequest;
use App\Http\Requests\Admin\Safety\ResolveIncidentRequest;
use App\Http\Requests\Admin\Safety\TriageIncidentRequest;
use App\Models\SafetyIncident;
use App\Repositories\Contracts\Safety\SafetyIncidentRepositoryInterface;
use App\Services\Admin\AdminExportService;
use App\Services\Audit\AuditTrailService;
use App\Services\Safety\IncidentStaffService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Safety Center (incidents.view / manage / view_evidence / export_restricted). Thin: the route `can:` and the FormRequest authorize
 * through SafetyIncidentPolicy, one service call does the work and audits it. The overview needs `incidents.view`; the evidence tab,
 * the attachment stream and the exports need their own permissions and write an audit entry every time.
 */
final class SafetyIncidentController extends AdminController
{
    public function __construct(
        private readonly IncidentStaffService $service,
        private readonly SafetyIncidentRepositoryInterface $incidents,
    ) {}

    public function index(SafetyIncidentsDataTable $table): mixed
    {
        return $table->render('admin.safety-incidents.index', ['urgentNew' => $this->incidents->countUrgentNew(), 'active' => $this->incidents->countActive()]);
    }

    public function show(SafetyIncident $incident): View
    {
        $incident->load(['reporter', 'booking', 'assignedAdmin']);

        return view('admin.safety-incidents.show', [
            'incident' => $incident,
            'events' => $this->service->timeline($incident),
            'assignees' => $this->service->assignees(),
        ]);
    }

    public function activity(SafetyIncident $incident, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($incident)]);
    }

    /**
     * Evidence tab: narrative, callback number, location snapshot, timeline bodies, files, trusted-contact actions. Audited per load.
     */
    public function evidence(SafetyIncident $incident): View
    {
        return view('admin.safety-incidents._evidence', ['incident' => $incident, 'evidence' => $this->service->evidence($incident, $this->actor())]);
    }

    public function attachment(SafetyIncident $incident, string $attachment): StreamedResponse
    {
        return $this->service->streamAttachment($incident, $attachment, $this->actor());
    }

    public function acknowledge(AcknowledgeIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->acknowledge($incident, $this->actor());

        return $this->done(__('safety.admin.done.acknowledged'), $incident);
    }

    public function assign(AssignIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->assign($incident, $this->actor(), $request->adminId(), $request->reason());

        return $this->done(__('safety.admin.done.assigned'), $incident);
    }

    public function note(NoteIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->addNote($incident, $this->actor(), (string) $request->validated('body'));

        return $this->done(__('safety.admin.done.note'), $incident);
    }

    public function message(MessageIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->messageUser($incident, $this->actor(), (string) $request->validated('body'));

        return $this->done(__('safety.admin.done.message'), $incident);
    }

    public function triage(TriageIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->triage($incident, $this->actor(), $request->severity(), $request->priority(), (string) $request->reason());

        return $this->done(__('safety.admin.done.triage'), $incident);
    }

    public function escalate(EscalateIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->escalate($incident, $this->actor(), $request->level(), (string) $request->reason());

        return $this->done(__('safety.admin.done.escalated'), $incident);
    }

    public function resolve(ResolveIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->resolve($incident, $this->actor(), $request->outcome(), (string) $request->reason(), $request->publicMessage());

        return $this->done(__('safety.admin.done.resolved'), $incident);
    }

    public function reopen(ReopenIncidentRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->reopen($incident, $this->actor(), (string) $request->reason());

        return $this->done(__('safety.admin.done.reopened'), $incident);
    }

    public function legalHold(LegalHoldRequest $request, SafetyIncident $incident): AdminFormResponse
    {
        $this->service->setLegalHold($incident, $this->actor(), $request->hold(), (string) $request->reason());

        return $this->done(__($request->hold() ? 'safety.admin.done.hold_set' : 'safety.admin.done.hold_released'), $incident);
    }

    public function export(Request $request, SafetyIncidentsDataTable $table, AdminExportService $exports): AdminFormResponse
    {
        $export = $exports->queue($table, IncidentFilterData::fromRequest($request)->toArray(), $this->actor());

        return $this->formResponse(__('admin.exports.queued'))->with('status_url', route('admin.exports.show', $export));
    }

    /**
     * The restricted evidence bundle of one case as a JSON download (audited, recorded on the timeline).
     */
    public function bundle(SafetyIncident $incident): JsonResponse
    {
        $bundle = $this->service->evidenceBundle($incident, $this->actor());

        return response()->json($bundle, 200, [
            'Content-Disposition' => 'attachment; filename="'.$incident->number.'-evidence.json"',
            'Cache-Control' => 'private, no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function done(string $message, SafetyIncident $incident): AdminFormResponse
    {
        return $this->formResponse($message)->redirect(route('admin.safety-incidents.show', $incident));
    }
}
