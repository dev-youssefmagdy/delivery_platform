<?php

namespace App\Http\Resources\Api\V1\Trip;

use App\Models\BookingReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property BookingReport $resource
 */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $report = $this->resource;

        return [
            'id' => $report->uuid,
            'category' => $report->category->value,
            'category_label' => $report->category->label(),
            'status' => $report->status->value,
            'status_label' => $report->status->label(),
            'created_at' => $report->created_at->toIso8601ZuluString(),
        ];
    }
}
