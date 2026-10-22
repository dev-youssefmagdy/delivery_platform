<?php

namespace App\Services\Notification;

use App\DTOs\Notification\NotificationTemplateData;
use App\Enums\Common\AuditAction;
use App\Enums\Notification\NotificationEvent;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Models\Admin;
use App\Models\NotificationTemplate;
use App\Repositories\Contracts\Notification\NotificationTemplateRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Admin editing of notification templates (SRS §14.1): audited writes with optimistic locking, a version that grows with
 * every change of the text (deliveries remember the version they used), and placeholders limited to the variables the
 * event really provides. A template is never deleted: deactivating it makes the platform fall back to Arabic or to the
 * built-in text, so an event can never end up with no words.
 */
final class NotificationTemplateService
{
    public function __construct(
        private readonly NotificationTemplateRepositoryInterface $templates,
        private readonly TemplateRenderer $renderer,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * @throws BusinessRuleViolation `notification_template_exists`, `notification_template_channel_unused`, `notification_template_unknown_variable`
     */
    public function create(NotificationTemplateData $data, ?Admin $actor): NotificationTemplate
    {
        $event = NotificationEvent::tryFrom((string) $data->eventKey) ?? throw BusinessRuleViolation::make('notification_template_event_unknown', 'event_key');
        $channel = $data->channel ?? throw BusinessRuleViolation::make('notification_template_channel_unused', 'channel');

        if (! in_array($channel, $event->defaultChannels(), true)) {
            throw BusinessRuleViolation::make('notification_template_channel_unused', 'channel');
        }

        $this->assertVariables($event, $data);

        try {
            return $this->db->transaction(function () use ($data, $event, $channel, $actor): NotificationTemplate {
                /** @var NotificationTemplate $template */
                $template = $this->templates->create([
                    'event_key' => $event->value,
                    'channel' => $channel->value,
                    'locale' => (string) $data->locale,
                    'title' => $data->title,
                    'body' => $data->body,
                    'active' => $data->active,
                    'updated_by_admin_id' => $actor?->id,
                ]);
                $this->audit->record(AuditAction::Created, $template, $actor, after: $template->auditSnapshot());

                return $template;
            });
        } catch (UniqueConstraintViolationException) {
            throw BusinessRuleViolation::make('notification_template_exists', 'event_key');
        }
    }

    /**
     * @throws ConflictException `stale_version` when someone else saved first
     */
    public function update(NotificationTemplate $template, NotificationTemplateData $data, ?Admin $actor): NotificationTemplate
    {
        $event = $template->event();

        if ($event !== null) {
            $this->assertVariables($event, $data);
        }

        return $this->db->transaction(function () use ($template, $data, $actor): NotificationTemplate {
            $locked = $this->lock($template);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            $before = $locked->auditSnapshot();
            $textChanged = $locked->title !== $data->title || $locked->body !== $data->body;
            $this->templates->update($locked, [
                'title' => $data->title,
                'body' => $data->body,
                'active' => $data->active,
                'version' => $locked->version + ($textChanged ? 1 : 0),
                'lock_version' => $locked->lock_version + 1,
                'updated_by_admin_id' => $actor?->id,
            ]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    public function setActive(NotificationTemplate $template, bool $active, ?string $reason, ?Admin $actor): NotificationTemplate
    {
        return $this->db->transaction(function () use ($template, $active, $reason, $actor): NotificationTemplate {
            $locked = $this->lock($template);

            if ($locked->active === $active) {
                return $locked;
            }

            $this->templates->update($locked, ['active' => $active, 'lock_version' => $locked->lock_version + 1, 'updated_by_admin_id' => $actor?->id]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['active' => ! $active], ['active' => $active], $reason);

            return $locked;
        });
    }

    private function assertVariables(NotificationEvent $event, NotificationTemplateData $data): void
    {
        $unknown = [
            ...$this->renderer->unknown($data->body, $event->variables()),
            ...$this->renderer->unknown((string) $data->title, $event->variables()),
        ];

        if ($unknown !== []) {
            throw BusinessRuleViolation::make('notification_template_unknown_variable', 'body', ['variables' => implode(', ', array_unique($unknown))]);
        }
    }

    private function lock(NotificationTemplate $template): NotificationTemplate
    {
        $locked = $this->templates->lockById($template->id);

        return $locked instanceof NotificationTemplate ? $locked : throw (new ModelNotFoundException)->setModel(NotificationTemplate::class);
    }
}
