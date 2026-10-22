<?php

namespace App\Http\Resources\Api\V1\Notification;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One inbox entry. `link` tells the app what a tap opens (`booking`, `payout`, `documents` ...) and is absent for entries
 * that only inform; the app must cope with a target that no longer exists (SRS §21).
 *
 * @property AppNotification $resource
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $notification = $this->resource;

        return [
            'id' => $notification->uuid,
            'event' => $notification->event_key,
            'category' => $notification->category->value,
            'title' => $notification->title,
            'body' => $notification->body,
            'read' => $notification->read_at !== null,
            'link' => $notification->link_type === null ? null : ['type' => $notification->link_type, 'id' => $notification->link_id],
            'created_at' => $notification->created_at->toIso8601ZuluString(),
            'read_at' => $notification->read_at?->toIso8601ZuluString(),
        ];
    }
}
