<?php

namespace App\Services\Notification;

use App\DTOs\Notification\NotificationMessage;
use App\DTOs\Notification\PushCampaignData;
use App\Enums\Common\AuditAction;
use App\Enums\Common\Locale;
use App\Enums\Notification\CampaignStatus;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use App\Enums\Notification\NotificationEvent;
use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\ConflictException;
use App\Jobs\Notification\RunPushCampaign;
use App\Models\Admin;
use App\Models\PushCampaign;
use App\Repositories\Contracts\Identity\UserDeviceRepositoryInterface;
use App\Repositories\Contracts\Notification\AppNotificationRepositoryInterface;
use App\Repositories\Contracts\Notification\NotificationPreferenceRepositoryInterface;
use App\Repositories\Contracts\Notification\PushCampaignRepositoryInterface;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Marketing push campaigns (SRS §14.1, `push_campaigns.manage`). A campaign reaches only people who explicitly opted in to
 * marketing push (the marketing category is off until the person turns it on), who have a registered device in the
 * audience's app and an active account; the preference check runs again inside `NotificationService`. Every step that
 * changes a campaign is audited with the acting admin; sending and cancelling need a reason. A scheduled campaign is
 * started by `push-campaigns:dispatch-due`; the run is idempotent (one notification per person and app, by dedupe key).
 */
final class PushCampaignService
{
    private const MINIMUM_LEAD_MINUTES = 1;

    public function __construct(
        private readonly PushCampaignRepositoryInterface $campaigns,
        private readonly NotificationPreferenceRepositoryInterface $preferences,
        private readonly UserDeviceRepositoryInterface $devices,
        private readonly AppNotificationRepositoryInterface $inbox,
        private readonly NotificationService $notifications,
        private readonly AuditLogger $audit,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * People a campaign for this audience would reach right now.
     */
    public function estimate(PushCampaign|PushCampaignData $campaign): int
    {
        return $this->preferences->countOptedInUsersWithDevice(NotificationCategory::Marketing, NotificationChannel::Push, $campaign->audience->apps());
    }

    public function create(PushCampaignData $data, ?Admin $actor): PushCampaign
    {
        $this->assertContent($data);

        return $this->db->transaction(function () use ($data, $actor): PushCampaign {
            /** @var PushCampaign $campaign */
            $campaign = $this->campaigns->create($this->attributes($data) + ['status' => CampaignStatus::Draft, 'created_by_admin_id' => $actor?->id]);
            $this->audit->record(AuditAction::Created, $campaign, $actor, after: $campaign->auditSnapshot());

            return $campaign;
        });
    }

    /**
     * @throws ConflictException `stale_version`
     * @throws BusinessRuleViolation `push_campaign_not_editable`
     */
    public function update(PushCampaign $campaign, PushCampaignData $data, ?Admin $actor): PushCampaign
    {
        $this->assertContent($data);

        return $this->db->transaction(function () use ($campaign, $data, $actor): PushCampaign {
            $locked = $this->lock($campaign);

            if ($data->lockVersion !== null && $data->lockVersion !== $locked->lock_version) {
                throw ConflictException::make('stale_version');
            }

            if (! $locked->status->isEditable()) {
                throw BusinessRuleViolation::make('push_campaign_not_editable');
            }

            if ($locked->status === CampaignStatus::Scheduled) {
                $this->assertSchedulable($data->scheduledAt);
            }

            $before = $locked->auditSnapshot();
            $this->campaigns->update($locked, $this->attributes($data) + ['lock_version' => $locked->lock_version + 1]);
            $this->audit->recordChanges(AuditAction::Updated, $locked, $actor, $before, $locked->auditSnapshot());

            return $locked;
        });
    }

    /**
     * @throws BusinessRuleViolation `push_campaign_schedule_invalid`, `push_campaign_not_editable`
     */
    public function schedule(PushCampaign $campaign, ?Admin $actor): PushCampaign
    {
        return $this->db->transaction(function () use ($campaign, $actor): PushCampaign {
            $locked = $this->lock($campaign);

            if ($locked->status !== CampaignStatus::Draft) {
                throw BusinessRuleViolation::make('push_campaign_not_editable');
            }

            $this->assertSchedulable($locked->scheduled_at === null ? null : CarbonImmutable::instance($locked->scheduled_at));
            $this->campaigns->update($locked, ['status' => CampaignStatus::Scheduled, 'lock_version' => $locked->lock_version + 1]);
            $this->audit->record(AuditAction::Published, $locked, $actor, ['status' => CampaignStatus::Draft->value], ['status' => CampaignStatus::Scheduled->value, 'scheduled_at' => $locked->scheduled_at?->toIso8601String()]);

            return $locked;
        });
    }

    /**
     * Starts the campaign now (dangerous: it messages real people, so a reason is required and audited).
     */
    public function sendNow(PushCampaign $campaign, string $reason, ?Admin $actor): PushCampaign
    {
        return $this->db->transaction(function () use ($campaign, $reason, $actor): PushCampaign {
            $locked = $this->lock($campaign);

            if (! $locked->status->isEditable()) {
                throw BusinessRuleViolation::make('push_campaign_not_editable');
            }

            $this->start($locked, $actor, $reason, ['audience' => $locked->audience->value, 'estimated_recipients' => $this->estimate($locked)]);

            return $locked;
        });
    }

    public function cancel(PushCampaign $campaign, string $reason, ?Admin $actor): PushCampaign
    {
        return $this->db->transaction(function () use ($campaign, $reason, $actor): PushCampaign {
            $locked = $this->lock($campaign);

            if (! $locked->status->isEditable()) {
                throw BusinessRuleViolation::make('push_campaign_not_editable');
            }

            $from = $locked->status;
            $this->campaigns->update($locked, [
                'status' => CampaignStatus::Cancelled,
                'cancelled_by_admin_id' => $actor?->id,
                'cancel_reason' => $reason,
                'lock_version' => $locked->lock_version + 1,
            ]);
            $this->audit->record(AuditAction::StatusChanged, $locked, $actor, ['status' => $from->value], ['status' => CampaignStatus::Cancelled->value], $reason);

            return $locked;
        });
    }

    /**
     * Scheduler entry: starts every scheduled campaign whose time has come. Safe to run repeatedly and concurrently
     * (each campaign is claimed under its row lock).
     *
     * @return int campaigns started
     */
    public function startDue(?CarbonImmutable $now = null): int
    {
        $started = 0;

        foreach ($this->campaigns->due($now ?? CarbonImmutable::now(), 20) as $due) {
            $this->db->transaction(function () use ($due, &$started): void {
                $locked = $this->lock($due);

                if ($locked->status === CampaignStatus::Scheduled) {
                    $this->start($locked, null, null, ['trigger' => 'schedule']);
                    $started++;
                }
            });
        }

        return $started;
    }

    /**
     * Runs inside the queued job: one notification per opted-in person and app. Idempotent by dedupe key.
     */
    public function run(int $campaignId): void
    {
        $campaign = $this->campaigns->find($campaignId);

        if (! $campaign instanceof PushCampaign || $campaign->status !== CampaignStatus::Sending) {
            return;
        }

        $apps = $campaign->audience->apps();
        $pairs = 0;

        foreach ($this->preferences->optedInUsersWithDevice(NotificationCategory::Marketing, NotificationChannel::Push, $apps) as $user) {
            [$title, $body] = $campaign->contentFor(Locale::tryFromValue($user->preferredLocale())->value ?? Locale::default()->value);

            foreach ($apps as $app) {
                if ($this->devices->withPushToken($user, $app)->isEmpty()) {
                    continue;
                }

                $pairs++;
                $this->notifications->send(new NotificationMessage(
                    NotificationEvent::MarketingCampaign,
                    $user,
                    $app,
                    linkId: null,
                    dedupeKey: 'campaign:'.$campaign->id.':'.$app->value,
                    campaignId: $campaign->id,
                    content: ['title' => $title, 'body' => $body],
                ));
            }
        }

        $delivered = $this->inbox->countForCampaign($campaign->id);
        $this->campaigns->update($campaign, [
            'status' => CampaignStatus::Sent,
            'finished_at' => now(),
            'recipients_count' => $delivered,
            'skipped_count' => max(0, $pairs - $delivered),
        ]);
    }

    /**
     * Moves a locked campaign to `sending` and queues the run after the transaction commits.
     *
     * @param array<string, mixed> $meta
     */
    private function start(PushCampaign $locked, ?Admin $actor, ?string $reason, array $meta): void
    {
        $from = $locked->status;
        $this->campaigns->update($locked, ['status' => CampaignStatus::Sending, 'started_at' => now(), 'lock_version' => $locked->lock_version + 1]);
        $this->audit->record(AuditAction::Published, $locked, $actor, ['status' => $from->value], ['status' => CampaignStatus::Sending->value], $reason, $meta);

        RunPushCampaign::dispatch($locked->id)->afterCommit();
    }

    private function assertContent(PushCampaignData $data): void
    {
        if (($data->titleEn === null) !== ($data->bodyEn === null)) {
            throw BusinessRuleViolation::make('push_campaign_english_incomplete', 'title.en');
        }
    }

    private function assertSchedulable(?CarbonImmutable $at): void
    {
        if ($at === null || $at->lessThan(CarbonImmutable::now()->addMinutes(self::MINIMUM_LEAD_MINUTES))) {
            throw BusinessRuleViolation::make('push_campaign_schedule_invalid', 'scheduled_at');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(PushCampaignData $data): array
    {
        return [
            'name' => $data->name,
            'audience' => $data->audience,
            'title_ar' => $data->titleAr,
            'body_ar' => $data->bodyAr,
            'title_en' => $data->titleEn,
            'body_en' => $data->bodyEn,
            'scheduled_at' => $data->scheduledAt,
        ];
    }

    private function lock(PushCampaign $campaign): PushCampaign
    {
        $locked = $this->campaigns->lockById($campaign->id);

        return $locked instanceof PushCampaign ? $locked : throw (new ModelNotFoundException)->setModel(PushCampaign::class);
    }
}
