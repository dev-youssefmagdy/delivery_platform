<?php

namespace App\DataTables\Admin\Notification;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Notification\NotificationDeliveryFilterData;
use App\Enums\Notification\NotificationEvent;
use App\Models\NotificationDelivery;
use App\Repositories\Contracts\Notification\NotificationDeliveryRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yajra\DataTables\EloquentDataTable;

/**
 * Delivery log (Plan Appendix C: channel, status, event key, date range). Read-only; a failed delivery can be sent
 * again with a reason. The recipient is shown as a type and id only: the log never prints names, numbers or addresses.
 */
final class NotificationDeliveriesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'notification-deliveries-table';

    protected array $defaultOrder = [[0, 'desc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly NotificationDeliveryRepositoryInterface $deliveries)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<NotificationDelivery>
     */
    public function query(): Builder
    {
        return $this->deliveries->datatableQuery(NotificationDeliveryFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'notification_deliveries.id'),
            $this->column('event_key', __('admin.notification_deliveries.fields.event'), orderable: true, name: 'notification_deliveries.event_key'),
            $this->column('channel', __('admin.notification_deliveries.fields.channel'), orderable: true, name: 'notification_deliveries.channel'),
            $this->column('status', __('admin.notification_deliveries.fields.status'), orderable: true, name: 'notification_deliveries.status'),
            $this->column('recipient', __('admin.notification_deliveries.fields.recipient')),
            $this->column('attempts', __('admin.notification_deliveries.fields.attempts'), orderable: true, name: 'notification_deliveries.attempts'),
            $this->column('error_code', __('admin.notification_deliveries.fields.error')),
            $this->column('created_at', __('admin.common.created_at'), orderable: true, name: 'notification_deliveries.created_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'event_key', 'channel', 'status', 'recipient', 'attempts', 'error_code', 'created_at', 'actions']);

        $table->editColumn('event_key', static function (Model $row): string {
            if (! $row instanceof NotificationDelivery) {
                return '';
            }

            return '<a href="'.e(route('admin.notification-deliveries.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e(NotificationEvent::tryFrom($row->event_key)?->label() ?? $row->event_key).'</a><div class="ltr-only text-xs text-slate-500">'.e($row->event_key).'</div>';
        });
        $this->rawColumn('event_key');
        $table->editColumn('channel', static fn (Model $row): string => $row instanceof NotificationDelivery ? e($row->channel->label()) : '');
        $this->enumBadgeColumn($table, 'status');
        $table->addColumn('recipient', static fn (NotificationDelivery $delivery): string => e(__('admin.notification_deliveries.recipient_'.$delivery->recipient_type)).' <span class="ltr-only">#'.e((string) $delivery->recipient_id).'</span>');
        $this->rawColumn('recipient');
        $table->editColumn('error_code', static fn (Model $row): string => $row instanceof NotificationDelivery && $row->error_code !== null ? '<span class="ltr-only text-xs">'.e($row->error_code).'</span>' : '—');
        $this->rawColumn('error_code');
        $this->dateColumn($table, 'created_at');

        $this->actionsColumn($table, static fn (NotificationDelivery $delivery): array => [
            TableAction::view(route('admin.notification-deliveries.show', $delivery), $delivery),
            TableAction::confirm(
                __('admin.notification_deliveries.actions.retry'),
                route('admin.notification-deliveries.retry', $delivery),
                'retry',
                $delivery,
                icon: 'refresh',
                variant: 'warning',
                reasonRequired: true,
                confirm: ['title' => __('admin.notification_deliveries.confirm.title'), 'message' => __('admin.notification_deliveries.confirm.message'), 'confirm_label' => __('admin.notification_deliveries.actions.retry')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
