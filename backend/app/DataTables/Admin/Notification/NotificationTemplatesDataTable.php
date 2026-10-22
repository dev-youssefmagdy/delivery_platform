<?php

namespace App\DataTables\Admin\Notification;

use App\DataTables\Admin\AdminDataTable;
use App\DTOs\Notification\NotificationTemplateFilterData;
use App\Models\NotificationTemplate;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;
use App\Support\Admin\Tables\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;

/**
 * Notification templates (Plan Appendix C style filters: event key, category, channel, language, active).
 */
final class NotificationTemplatesDataTable extends AdminDataTable
{
    public const TABLE_ID = 'notification-templates-table';

    protected array $defaultOrder = [[1, 'asc']];

    protected bool $globalSearch = false;

    public function __construct(private readonly NotificationTemplateRepositoryInterface $templates)
    {
        parent::__construct();
    }

    public function tableId(): string
    {
        return self::TABLE_ID;
    }

    /**
     * @return Builder<NotificationTemplate>
     */
    public function query(): Builder
    {
        return $this->templates->datatableQuery(NotificationTemplateFilterData::fromRequest($this->filterRequest()));
    }

    protected function columns(): array
    {
        return [
            $this->column('id', '#', orderable: true, name: 'notification_templates.id'),
            $this->column('event_key', __('admin.notification_templates.fields.event'), orderable: true, name: 'notification_templates.event_key'),
            $this->column('channel', __('admin.notification_templates.fields.channel'), orderable: true, name: 'notification_templates.channel'),
            $this->column('locale', __('admin.notification_templates.fields.locale'), orderable: true, name: 'notification_templates.locale'),
            $this->column('body', __('admin.notification_templates.fields.body')),
            $this->column('version', __('admin.notification_templates.fields.version'), orderable: true, name: 'notification_templates.version'),
            $this->column('active', __('admin.notification_templates.fields.active'), orderable: true, name: 'notification_templates.active'),
            $this->column('updated_at', __('admin.common.updated_at'), orderable: true, name: 'notification_templates.updated_at'),
            $this->actionsColumnDefinition(),
        ];
    }

    protected function configure(EloquentDataTable $table): void
    {
        $table->only(['id', 'event_key', 'channel', 'locale', 'body', 'version', 'active', 'updated_at', 'actions']);

        $table->editColumn('event_key', static function (Model $row): string {
            if (! $row instanceof NotificationTemplate) {
                return '';
            }

            $label = $row->event()?->label() ?? $row->event_key;

            return '<a href="'.e(route('admin.notification-templates.show', $row)).'" class="font-medium text-brand-700 hover:underline">'.e($label).'</a><div class="ltr-only text-xs text-slate-500">'.e($row->event_key).'</div>';
        });
        $this->rawColumn('event_key');
        $table->editColumn('channel', static fn (Model $row): string => $row instanceof NotificationTemplate ? e($row->channel->label()) : '');
        $table->editColumn('locale', static fn (Model $row): string => $row instanceof NotificationTemplate ? e(\App\Enums\Common\Locale::tryFrom($row->locale)?->label() ?? $row->locale) : '');
        $table->editColumn('body', static fn (Model $row): string => $row instanceof NotificationTemplate ? e(Str::limit($row->body, 70)) : '');
        $this->boolColumn($table, 'active');
        $this->dateColumn($table, 'updated_at');

        $this->actionsColumn($table, static fn (NotificationTemplate $template): array => [
            TableAction::view(route('admin.notification-templates.show', $template), $template),
            TableAction::edit(route('admin.notification-templates.edit', $template), $template),
            TableAction::confirm(
                __('admin.catalog.actions.activate'),
                route('admin.notification-templates.activate', $template),
                'activate',
                $template,
                icon: 'play',
                variant: 'success',
                confirm: ['title' => __('admin.notification_templates.confirm.activate_title'), 'message' => __('admin.notification_templates.confirm.activate_message')],
                reloadTable: self::TABLE_ID,
            ),
            TableAction::confirm(
                __('admin.catalog.actions.deactivate'),
                route('admin.notification-templates.deactivate', $template),
                'deactivate',
                $template,
                icon: 'pause',
                reasonRequired: true,
                confirm: ['title' => __('admin.notification_templates.confirm.deactivate_title'), 'message' => __('admin.notification_templates.confirm.deactivate_message')],
                reloadTable: self::TABLE_ID,
            ),
        ]);
    }
}
