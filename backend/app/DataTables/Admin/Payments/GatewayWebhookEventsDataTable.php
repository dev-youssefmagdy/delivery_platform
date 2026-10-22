<?php

namespace App\DataTables\Admin\Payments;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Payments\WebhookEventFilterData;
use App\Models\GatewayWebhookEvent;
use App\Repositories\Contracts\Payments\GatewayWebhookEventRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Gateway webhook events (filters: outcome, gateway, kind, signature, reference, received range, "needs attention").
 * Failures are highlighted; a failed / unmatched / mismatching event can be processed again with a reason.
 */
final class GatewayWebhookEventsDataTable extends AdminDataTable
{
    public const TABLE_ID = 'gateway-webhook-events-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly GatewayWebhookEventRepositoryInterface $events)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<GatewayWebhookEvent>
     */
    public function query(): Builder
    {
        return $this->events->datatableQuery(WebhookEventFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'gateway_webhook_events.id'),
            $this->column('event', __('admin.webhooks.fields.event')),
            $this->column('gateway', __('admin.webhooks.fields.gateway')),
            $this->column('kind', __('admin.webhooks.fields.kind')),
            $this->column('outcome', __('admin.webhooks.fields.outcome'), orderable: true, name: 'gateway_webhook_events.outcome'),
            $this->column('signature_valid', __('admin.webhooks.fields.signature')),
            $this->column('gateway_reference', __('admin.webhooks.fields.reference')),
            $this->column('payment', __('admin.webhooks.fields.payment')),
            $this->column('attempts', __('admin.webhooks.fields.attempts')),
            $this->column('received_at', __('admin.webhooks.fields.received'), orderable: true, name: 'gateway_webhook_events.received_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'event', 'gateway', 'kind', 'outcome', 'signature_valid', 'gateway_reference', 'payment', 'attempts', 'received_at', 'actions']);

        $table->addColumn('event', static fn (GatewayWebhookEvent $event): string => '<a href="'.e(route('admin.gateway-webhook-events.show', $event)).'" class="ltr-only font-medium text-brand-700 hover:underline">'.e(Str::limit($event->event_id, 24)).'</a>');
        $this->rawColumn('event');
        $table->editColumn('gateway', static fn (Model $row): string => $row instanceof GatewayWebhookEvent ? e($row->gateway->label()) : '');
        $table->editColumn('kind', static fn (Model $row): string => $row instanceof GatewayWebhookEvent ? e($row->kind?->label() ?? '—') : '');
        $this->enumBadgeColumn($table, 'outcome');
        $this->boolColumn($table, 'signature_valid');
        $table->editColumn('gateway_reference', static fn (Model $row): string => $row instanceof GatewayWebhookEvent ? '<span class="ltr-only">'.e((string) ($row->gateway_reference ?? '—')).'</span>' : '');
        $this->rawColumn('gateway_reference');
        $table->addColumn('payment', static fn (GatewayWebhookEvent $event): string => $event->payment === null ? '—' : '<a href="'.e(route('admin.payments.show', $event->payment)).'" class="ltr-only text-brand-700 hover:underline">'.e(Str::substr($event->payment->uuid, 0, 8)).'</a>');
        $this->rawColumn('payment');
        $this->dateColumn($table, 'received_at');

        $this->actionsColumn($table, static fn (GatewayWebhookEvent $event): array => [
            TableAction::view(route('admin.gateway-webhook-events.show', $event), $event),
            TableAction::confirm(
                __('admin.webhooks.actions.reprocess'),
                route('admin.gateway-webhook-events.reprocess', $event),
                'reprocess',
                $event,
                icon: 'refresh',
                variant: 'warning',
                reasonRequired: true,
                confirm: ['title' => __('admin.webhooks.confirm.title'), 'message' => __('admin.webhooks.confirm.message'), 'confirm_label' => __('admin.webhooks.actions.reprocess')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
