<?php

namespace App\Http\Resources\Api\V1\Trip;

use App\Enums\Booking\ReportCategory;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking report as the apps see it: the P2.5 shape (`category`, `status` open|closed) plus the support ticket it became.
 *
 * @property SupportTicket $resource
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ticket = $this->resource;
        $category = ReportCategory::tryFrom((string) ($ticket->context['report_category'] ?? ''));
        $status = $ticket->status->isActive() ? 'open' : 'closed';

        return [
            'id' => $ticket->uuid,
            'category' => $category?->value ?? $ticket->category->value,
            'category_label' => $category?->label() ?? $ticket->category->label(),
            'status' => $status,
            'status_label' => __("enums.report_status.{$status}"),
            'ticket_id' => $ticket->uuid,
            'ticket_number' => $ticket->number,
            'created_at' => $ticket->created_at->toIso8601ZuluString(),
        ];
    }
}
