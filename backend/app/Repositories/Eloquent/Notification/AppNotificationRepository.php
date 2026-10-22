<?php

namespace App\Repositories\Eloquent\Notification;

use App\Enums\Identity\ClientApp;
use App\Models\AppNotification;
use App\Repositories\Contracts\Notification\AppNotificationRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<AppNotification>
 */
final class AppNotificationRepository extends BaseRepository implements AppNotificationRepositoryInterface
{
    public function __construct(AppNotification $model)
    {
        parent::__construct($model);
    }

    public function findByDedupeKey(string $recipientType, int $recipientId, string $dedupeKey): ?AppNotification
    {
        return $this->query()->where('recipient_type', $recipientType)->where('recipient_id', $recipientId)->where('dedupe_key', $dedupeKey)->first();
    }

    public function inboxPage(string $recipientType, int $recipientId, ?ClientApp $app, int $perPage, bool $unreadOnly = false): LengthAwarePaginator
    {
        return $this->scoped($recipientType, $recipientId, $app)
            ->when($unreadOnly, static fn (Builder $query) => $query->whereNull('read_at'))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function unreadCount(string $recipientType, int $recipientId, ?ClientApp $app): int
    {
        return $this->scoped($recipientType, $recipientId, $app)->whereNull('read_at')->count();
    }

    public function findForRecipient(string $recipientType, int $recipientId, ?ClientApp $app, string $uuid): ?AppNotification
    {
        return $this->scoped($recipientType, $recipientId, $app)->where('uuid', $uuid)->first();
    }

    public function markAllRead(string $recipientType, int $recipientId, ?ClientApp $app): int
    {
        return $this->scoped($recipientType, $recipientId, $app)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function countForCampaign(int $campaignId): int
    {
        return $this->query()->where('campaign_id', $campaignId)->count();
    }

    public function latest(string $recipientType, int $recipientId, int $limit): Collection
    {
        return $this->scoped($recipientType, $recipientId, null)->orderByDesc('id')->limit($limit)->get();
    }

    /**
     * @return Builder<AppNotification>
     */
    private function scoped(string $recipientType, int $recipientId, ?ClientApp $app): Builder
    {
        return $this->query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->when($app !== null, static fn (Builder $query) => $query->where('app', $app?->value));
    }
}
