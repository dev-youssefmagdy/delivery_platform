<?php

namespace App\Services\Notification;

use App\DTOs\Notification\NotificationPreferenceUpdate;
use App\Enums\Notification\NotificationCategory;
use App\Enums\Notification\NotificationChannel;
use App\Exceptions\BusinessRuleViolation;
use App\Models\User;
use App\Repositories\Contracts\Notification\NotificationPreferenceRepositoryInterface;
use Illuminate\Database\DatabaseManager;

/**
 * A person's notification settings (SRS §14.1). A saved choice wins over the category default; essential categories keep
 * push and the inbox on whatever is saved (`NotificationCategory::locksChannel`), so a trip, fee or safety message can
 * never be switched off completely. Marketing needs an explicit opt-in.
 */
final class NotificationPreferenceService
{
    public function __construct(
        private readonly NotificationPreferenceRepositoryInterface $preferences,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Whether the channel may be used for the category right now.
     */
    public function allows(User $user, NotificationCategory $category, NotificationChannel $channel): bool
    {
        return $this->effective($user)[$category->value][$channel->value];
    }

    /**
     * Channels allowed for a category, in the order of `$candidates`.
     *
     * @param list<NotificationChannel> $candidates
     * @return list<NotificationChannel>
     */
    public function allowedChannels(User $user, NotificationCategory $category, array $candidates): array
    {
        $effective = $this->effective($user)[$category->value];

        return array_values(array_filter($candidates, static fn (NotificationChannel $channel): bool => $effective[$channel->value]));
    }

    /**
     * What the settings screen shows: every category with its channels, the saved/default state and which are locked.
     *
     * @return list<array{category: NotificationCategory, essential: bool, opt_in: bool, channels: list<array{channel: NotificationChannel, enabled: bool, locked: bool}>}>
     */
    public function matrix(User $user): array
    {
        $effective = $this->effective($user);
        $rows = [];

        foreach (NotificationCategory::forUsers() as $category) {
            $channels = [];

            foreach (NotificationChannel::preferenceChannels() as $channel) {
                $channels[] = [
                    'channel' => $channel,
                    'enabled' => $effective[$category->value][$channel->value],
                    'locked' => $category->locksChannel($channel),
                ];
            }

            $rows[] = ['category' => $category, 'essential' => $category->isEssential(), 'opt_in' => $category->requiresOptIn(), 'channels' => $channels];
        }

        return $rows;
    }

    /**
     * @param list<NotificationPreferenceUpdate> $updates
     *
     * @throws BusinessRuleViolation `notification_essential_locked` when a locked channel of an essential category is switched off
     */
    public function update(User $user, array $updates): void
    {
        foreach ($updates as $update) {
            if ($update->category === NotificationCategory::System) {
                throw BusinessRuleViolation::make('notification_category_unknown');
            }

            if (! $update->enabled && $update->category->locksChannel($update->channel)) {
                throw BusinessRuleViolation::make('notification_essential_locked');
            }
        }

        $this->db->transaction(function () use ($user, $updates): void {
            foreach ($updates as $update) {
                $this->preferences->upsert($user, $update->category, $update->channel, $update->enabled);
            }
        });
    }

    /**
     * @return array<string, array<string, bool>> category => channel => allowed
     */
    private function effective(User $user): array
    {
        $saved = [];

        foreach ($this->preferences->forUser($user) as $preference) {
            $saved[$preference->category->value][$preference->channel->value] = $preference->enabled;
        }

        $effective = [];

        foreach (NotificationCategory::cases() as $category) {
            foreach (NotificationChannel::cases() as $channel) {
                $effective[$category->value][$channel->value] = $category->locksChannel($channel)
                    ? true
                    : ($saved[$category->value][$channel->value] ?? $category->defaultEnabled($channel));
            }
        }

        return $effective;
    }
}
