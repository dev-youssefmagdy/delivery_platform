<?php

namespace App\Http\Controllers\Admin\Notification;

use App\DataTables\Admin\Notification\NotificationTemplatesDataTable;
use App\Enums\Common\Locale;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Notification\ActivateNotificationTemplateRequest;
use App\Http\Requests\Admin\Notification\DeactivateNotificationTemplateRequest;
use App\Http\Requests\Admin\Notification\PreviewNotificationTemplateRequest;
use App\Http\Requests\Admin\Notification\StoreNotificationTemplateRequest;
use App\Http\Requests\Admin\Notification\UpdateNotificationTemplateRequest;
use App\Models\NotificationTemplate;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;
use App\Services\Audit\AuditTrailService;
use App\Services\Notification\NotificationTemplatePreviewService;
use App\Services\Notification\NotificationTemplateService;
use App\Support\Http\AdminFormResponse;
use Illuminate\Contracts\View\View;

/**
 * Notification templates (notifications.manage): the words of every event per channel and language, with an ar/en
 * preview using sample variables. Thin: one service call each.
 */
final class NotificationTemplateController extends AdminController
{
    public function __construct(private readonly NotificationTemplateService $service) {}

    public function index(NotificationTemplatesDataTable $table): mixed
    {
        return $table->render('admin.notification-templates.index');
    }

    public function create(): View
    {
        return view('admin.notification-templates.create');
    }

    public function store(StoreNotificationTemplateRequest $request): AdminFormResponse
    {
        $template = $this->service->create($request->toDto(), $this->actor());

        return $this->formResponse(__('admin.notification_templates.created'))->redirect(route('admin.notification-templates.show', $template));
    }

    public function show(NotificationTemplate $notificationTemplate, NotificationTemplateRepositoryInterface $templates, NotificationTemplatePreviewService $previews): View
    {
        $event = $notificationTemplate->event();
        $siblings = $templates->siblings($notificationTemplate);

        return view('admin.notification-templates.show', [
            'template' => $notificationTemplate->load('updatedBy'),
            'event' => $event,
            'siblings' => $siblings,
            'preview' => $event === null ? [] : $previews->preview(
                $event,
                $notificationTemplate->channel,
                $siblings->mapWithKeys(static fn (NotificationTemplate $item): array => [$item->locale => ['title' => $item->title, 'body' => $item->body]])->all(),
            ),
        ]);
    }

    public function activity(NotificationTemplate $notificationTemplate, AuditTrailService $trail): View
    {
        return view('admin.partials.activity', ['logs' => $trail->latestFor($notificationTemplate)]);
    }

    public function edit(NotificationTemplate $notificationTemplate): View
    {
        return view('admin.notification-templates.edit', ['template' => $notificationTemplate->load('updatedBy'), 'event' => $notificationTemplate->event()]);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): AdminFormResponse
    {
        $this->service->update($notificationTemplate, $request->toDto(), $this->actor());

        return $this->formResponse(__('admin.notification_templates.updated'))->redirect(route('admin.notification-templates.show', $notificationTemplate));
    }

    public function preview(PreviewNotificationTemplateRequest $request, NotificationTemplatePreviewService $previews, NotificationTemplateRepositoryInterface $templates): AdminFormResponse
    {
        $event = NotificationEvent::from((string) $request->validated('event_key'));
        $channel = NotificationChannel::from((string) $request->validated('channel'));
        $content = [];

        foreach (Locale::cases() as $locale) {
            $saved = $templates->findFor($event->value, $channel, $locale->value);

            if ($saved !== null) {
                $content[$locale->value] = ['title' => $saved->title, 'body' => $saved->body];
            }
        }

        $variables = array_filter((array) $request->validated('variables'), static fn (mixed $value): bool => is_string($value) && $value !== '');

        return $this->formResponse()->replaceHtml('#template-preview-result', view('admin.notification-templates._preview-result', [
            'rows' => $previews->preview($event, $channel, $content, $variables),
        ])->render());
    }

    public function activate(ActivateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): AdminFormResponse
    {
        $this->service->setActive($notificationTemplate, true, null, $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.notification_templates.activated'), $notificationTemplate);
    }

    public function deactivate(DeactivateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): AdminFormResponse
    {
        $this->service->setActive($notificationTemplate, false, (string) $request->reason(), $this->actor());

        return $this->afterStateChange($request->boolean('from_show'), __('admin.notification_templates.deactivated'), $notificationTemplate);
    }

    private function afterStateChange(bool $fromShow, string $message, NotificationTemplate $template): AdminFormResponse
    {
        $response = $this->formResponse($message)->reloadTable(NotificationTemplatesDataTable::TABLE_ID);

        return $fromShow ? $response->redirect(route('admin.notification-templates.show', $template)) : $response;
    }
}
