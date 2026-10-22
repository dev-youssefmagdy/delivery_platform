<?php

namespace App\View\Components\Admin;

use App\Models\Admin;
use App\Models\AppNotification;
use App\Services\Notification\AdminNotificationLinker;
use App\Services\Notification\NotificationInboxService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * The top-bar bell of the admin panel (P3.6): unread dot and the latest notifications of the signed-in admin (for
 * example "your export is ready" with its download link). Reads only the admin's own inbox.
 */
class NotificationBell extends Component
{
    public int $unread = 0;

    /**
     * @var Collection<int, array{notification: AppNotification, url: string|null}>
     */
    public Collection $items;

    public function __construct(NotificationInboxService $inbox, AdminNotificationLinker $linker)
    {
        $admin = auth('admin')->user();
        $this->items = collect();

        if ($admin instanceof Admin) {
            $this->unread = $inbox->unreadCount($admin, null);
            $this->items = $inbox->latest($admin, max(1, (int) config('notifications.admin_bell_limit', 8)))
                ->map(static fn (AppNotification $notification): array => ['notification' => $notification, 'url' => $linker->urlFor($notification)]);
        }
    }

    public function render(): View
    {
        return view('components.admin.notification-bell');
    }
}
