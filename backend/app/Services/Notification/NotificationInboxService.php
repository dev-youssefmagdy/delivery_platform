<?php

namespace App\Services\Notification;

use App\Enums\Identity\ClientApp;
use App\Models\Admin;
use App\Models\AppNotification;
use App\Models\User;
use App\Repositories\Contracts\Notification\AppNotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * The inbox of a person: a mobile user sees the entries of the app they are signed in to, an admin sees their own. Every
 * read is scoped to the owner, so another person's notification is "not found" (no IDOR).
 */
final class NotificationInboxService
{
    public function __construct(private readonly AppNotificationRepositoryInterface $inbox) {}

    /**
     * @return LengthAwarePaginator<int, AppNotification>
     */
    public function page(User|Admin $recipient, ?ClientApp $app, int $perPage, bool $unreadOnly = false): LengthAwarePaginator
    {
        $max = max(1, (int) config('notifications.inbox_max_page_size', 50));

        return $this->inbox->inboxPage($this->type($recipient), $recipient->id, $app, max(1, min($max, $perPage)), $unreadOnly);
    }

    public function unreadCount(User|Admin $recipient, ?ClientApp $app): int
    {
        return $this->inbox->unreadCount($this->type($recipient), $recipient->id, $app);
    }

    /**
     * @return Collection<int, AppNotification>
     */
    public function latest(User|Admin $recipient, int $limit): Collection
    {
        return $this->inbox->latest($this->type($recipient), $recipient->id, $limit);
    }

    /**
     * Idempotent: reading twice keeps the first read time.
     */
    public function markRead(User|Admin $recipient, ?ClientApp $app, string $uuid): AppNotification
    {
        $notification = $this->inbox->findForRecipient($this->type($recipient), $recipient->id, $app, $uuid)
            ?? throw (new ModelNotFoundException)->setModel(AppNotification::class);

        if ($notification->read_at === null) {
            $this->inbox->update($notification, ['read_at' => now()]);
        }

        return $notification;
    }

    public function markAllRead(User|Admin $recipient, ?ClientApp $app): int
    {
        return $this->inbox->markAllRead($this->type($recipient), $recipient->id, $app);
    }

    private function type(User|Admin $recipient): string
    {
        return $recipient instanceof Admin ? 'admin' : 'user';
    }
}
