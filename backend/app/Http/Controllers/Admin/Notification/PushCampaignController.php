<?php

namespace App\Http\Controllers\Admin\Notification;

use App\DataTables\Admin\Notification\PushCampaignsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Notification\CancelPushCampaignRequest;
use App\Http\Requests\Admin\Notification\SchedulePushCampaignRequest;
use App\Http\Requests\Admin\Notification\SendPushCampaignRequest;
use App\Http\Requests\Admin\Notification\StorePushCampaignRequest;
use App\Http\Requests\Admin\Notification\UpdatePushCampaignRequest;
use App\Models\PushCampaign;
use App\Services\Audit\AuditTrailService;
use App\Services\Notification\PushCampaignService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Push campaigns (push_campaigns.manage): marketing messages to people who opted in, sent now or at a scheduled time.
 * Thin: one service call each; the service audits every change.
 */
final class PushCampaignController extends AdminController
{
    public function __construct(private readonly PushCampaignService $service) {}

    public function index(PushCampaignsDataTable $table): mixed
    {
        return $table->render('admin.push-campaigns.index');
    }

    public function create(): View
    {
        return view('admin.push-campaigns.create');
    }

    public function store(StorePushCampaignRequest $request): AdminFormResponse
    {
        $campaign = $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.push_campaigns.created'))->redirect(route('admin.push-campaigns.show', $campaign));
    }

    public function show(PushCampaign $pushCampaign): View
    {
        return view('admin.push-campaigns.show', [
            'campaign' => $pushCampaign->load('createdBy'),
            'estimate' => $pushCampaign->status->isEditable() ? $this->service->estimate($pushCampaign) : null,
        ]);
    }

    public function activity(PushCampaign $pushCampaign, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($pushCampaign)]);
    }

    public function edit(PushCampaign $pushCampaign): View
    {
        return view('admin.push-campaigns.edit', ['campaign' => $pushCampaign]);
    }

    public function update(UpdatePushCampaignRequest $request, PushCampaign $pushCampaign): AdminFormResponse
    {
        $this->service->update($pushCampaign, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.push_campaigns.updated'))->redirect(route('admin.push-campaigns.show', $pushCampaign));
    }

    public function schedule(SchedulePushCampaignRequest $request, PushCampaign $pushCampaign): AdminFormResponse
    {
        $this->service->schedule($pushCampaign, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.push_campaigns.scheduled'), $pushCampaign);
    }

    public function send(SendPushCampaignRequest $request, PushCampaign $pushCampaign): AdminFormResponse
    {
        $this->service->sendNow($pushCampaign, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.push_campaigns.started'), $pushCampaign);
    }

    public function cancel(CancelPushCampaignRequest $request, PushCampaign $pushCampaign): AdminFormResponse
    {
        $this->service->cancel($pushCampaign, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.push_campaigns.cancelled'), $pushCampaign);
    }

    private function afterStateChange(bool $fromShow, string $message, PushCampaign $campaign): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(PushCampaignsDataTable::TABLE_ID);

        return $fromShow ? $response->redirect(route('admin.push-campaigns.show', $campaign)) : $response;
    }
}
