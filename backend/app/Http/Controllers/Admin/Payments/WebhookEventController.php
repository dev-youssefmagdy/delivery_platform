<?php

namespace App\Http\Controllers\Admin\Payments;

use App\DataTables\Admin\Payments\GatewayWebhookEventsDataTable;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Payments\ReprocessWebhookRequest;
use App\Models\GatewayWebhookEvent;
use App\Services\Payments\PaymentWebhookService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Gateway webhook events (payments.view): every callback received, failures highlighted; a failed / unmatched event can be
 * processed again (finance.reconcile, mandatory reason, audited).
 */
final class WebhookEventController extends AdminController
{
    public function index(GatewayWebhookEventsDataTable $table): mixed
    {
        return $table->render('admin.gateway-webhook-events.index');
    }

    public function show(GatewayWebhookEvent $gatewayWebhookEvent): View
    {
        return view('admin.gateway-webhook-events.show', ['event' => $gatewayWebhookEvent->load('payment.booking')]);
    }

    public function reprocess(ReprocessWebhookRequest $request, GatewayWebhookEvent $gatewayWebhookEvent, PaymentWebhookService $webhooks): AdminFormResponse
    {
        $receipt = $webhooks->reprocess($gatewayWebhookEvent, $this->actor(), (string) $request->reason());

        return $this->formResponse(__('admin.webhooks.reprocessed', ['outcome' => $receipt->outcome->label()]))
            ->reloadTable(GatewayWebhookEventsDataTable::TABLE_ID)
            ->reload();
    }
}
