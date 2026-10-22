<?php

namespace App\Repositories\Contracts\Notification;

use App\Enums\Identity\ClientApp;
use App\Models\AppNotification;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<AppNotification>
 */
interface AppNotificationRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * The existing entry with this dedupe key for the recipient (the same event for the same person is one notification).
     */
    public function findByDedupeKey(string $recipientType, int $recipientId, string $dedupeKey): ?AppNotification;

    /**
     * @return LengthAwarePaginator<int, AppNotification> the recipient's inbox, newest first
     */
    public function inboxPage(string $recipientType, int $recipientId, ?ClientApp $app, int $perPage, bool $unreadOnly = false): LengthAwarePaginator;

    public function unreadCount(string $recipientType, int $recipientId, ?ClientApp $app): int;

    /**
     * Scoped to the owner: another person's entry is "not found" (no IDOR).
     */
    public function findForRecipient(string $recipientType, int $recipientId, ?ClientApp $app, string $uuid): ?AppNotification;

    public function markAllRead(string $recipientType, int $recipientId, ?ClientApp $app): int;

    /**
     * @return Collection<int, AppNotification> the newest entries (admin bell)
     */
    public function latest(string $recipientType, int $recipientId, int $limit): Collection;
}
