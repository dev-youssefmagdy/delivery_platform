<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\Models\OwnedDocument;
use App\Models\VehicleDocument;
use App\Services\Provider\DocumentAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A document as its owner sees it: status, reviewer's reason, validity dates and a 5-minute signed file URL. The
 * document number is only returned masked and the scan result never.
 *
 * @property OwnedDocument $resource
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $document = $this->resource;
        $number = $document->number;

        return [
            'id' => $document->uuid,
            'document_type' => $document->documentType->code,
            'vehicle_id' => $document instanceof VehicleDocument ? $document->vehicle->uuid : null,
            'status' => $document->status->value,
            'rejection_reason' => $document->rejection_reason,
            'number_masked' => $number === null || $number === '' ? null : '••••'.mb_substr($number, -3),
            'issued_at' => $document->issued_at?->toDateString(),
            'expires_at' => $document->expires_at?->toDateString(),
            'mime' => $document->mime,
            'size_bytes' => $document->size_bytes,
            'uploaded_at' => $document->created_at->toIso8601ZuluString(),
            'reviewed_at' => $document->reviewed_at?->toIso8601ZuluString(),
            'file_url' => $document->path === null ? null : app(DocumentAccessService::class)->ownerUrl($document),
        ];
    }
}
