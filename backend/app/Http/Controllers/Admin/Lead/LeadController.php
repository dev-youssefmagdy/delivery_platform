<?php

namespace App\Http\Controllers\Admin\Lead;

use App\DataTables\Admin\Lead\LeadsDataTable;
use App\DTOs\Lead\LeadUpdateData;
use App\Enums\Lead\LeadStatus;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Lead\TransitionLeadRequest;
use App\Http\Requests\Admin\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Repositories\Contracts\Lead\LeadRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Lead\LeadService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Onboarding leads from the public website (leads.view / leads.manage).
 */
final class LeadController extends AdminController
{
    public function __construct(private readonly LeadService $service, private readonly LeadRepositoryInterface $leads) {}

    public function index(LeadsDataTable $table): mixed
    {
        return $table->render('admin.leads.index', ['waiting' => $this->leads->countByStatus(LeadStatus::New->value)]);
    }

    public function show(Lead $lead, AuditTrailService $trail): View
    {
        return view('admin.leads.show', ['lead' => $lead->loadMissing(['city', 'serviceCategory', 'assignedAdmin:id,name']), 'logs' => $trail->latestFor($lead)]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): AdminFormResponse
    {
        $this->service->update($lead, $this->actor(), LeadUpdateData::fromRequest($request));

        return $this->formResponse(__('leads.updated'))->reload();
    }

    public function transition(TransitionLeadRequest $request, Lead $lead): AdminFormResponse
    {
        $this->service->transition($lead, $this->actor(), $request->target() ?? LeadStatus::New, $request->reason());

        return $this->formResponse(__('leads.moved'))->reloadTable(LeadsDataTable::TABLE_ID)->reload();
    }
}
