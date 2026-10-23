<?php

namespace App\Services\Notification;

use App\Enums\Admin\AdminExportStatus;
use App\Enums\Notification\NotificationLinkType;
use App\Models\AdminExport;
use App\Models\AppNotification;
use App\Repositories\Contracts\Admin\AdminExportRepositoryInterface;
use Illuminate\Support\Facades\URL;

/**
 * Where a click on an admin notification goes. An export notification opens a fresh signed download link (owner-only; the
 * notification itself never carries a link that expires). Anything else has no target yet.
 */
final class AdminNotificationLinker
{
    public function __construct(private readonly AdminExportRepositoryInterface $exports) {}

    public function urlFor(AppNotification $notification): ?string
    {
        if ($notification->link_type === NotificationLinkType::Incident->value && $notification->link_id !== null) {
            return route('admin.safety-incidents.show', $notification->link_id);
        }

        if ($notification->link_type === NotificationLinkType::Risk->value) {
            return $notification->link_id === null ? route('admin.risk-signals.index') : route('admin.risk-signals.show', $notification->link_id);
        }

        if ($notification->link_type !== NotificationLinkType::Export->value || $notification->link_id === null) {
            return null;
        }

        $export = $this->exports->findByUuid($notification->link_id);

        if (! $export instanceof AdminExport || $export->actor_type !== 'admin' || $export->actor_id !== $notification->recipient_id || $export->status !== AdminExportStatus::Completed) {
            return null;
        }

        return URL::temporarySignedRoute('admin.exports.download', now()->addMinutes((int) config('admin.exports.link_ttl_minutes', 1440)), ['export' => $export->uuid]);
    }
}
