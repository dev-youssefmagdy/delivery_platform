<?php

namespace App\Http\Controllers\Admin;

use App\Services\Notification\AdminNotificationLinker;
use App\Services\Notification\NotificationInboxService;
use App\Support\Http\AdminFormResponse;

/**
 * The signed-in admin's own notifications (top-bar bell): open one (marks it read and goes to its target, for example an
 * export download) or mark everything read. Always scoped to the acting admin, so another admin's id answers 404.
 */
final class AdminNotificationController extends AdminController
{
    public function __construct(private readonly NotificationInboxService $inbox, private readonly AdminNotificationLinker $linker) {}

    public function read(string $notification): AdminFormResponse
    {
        $read = $this->inbox->markRead($this->actor(), null, $notification);
        $url = $this->linker->urlFor($read);

        return $url === null ? $this->formResponse()->reload() : $this->formResponse()->redirect($url);
    }

    public function readAll(): AdminFormResponse
    {
        $this->inbox->markAllRead($this->actor(), null);

        return $this->formResponse()->reload();
    }
}
