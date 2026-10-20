<?php

namespace App\Http\Resources\Api\V1\Provider;

use App\Http\Resources\Api\V1\Catalog\TranslationMap;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the app needs to render the upload form of one document type.
 *
 * @property DocumentType $resource
 */
class DocumentTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->resource;

        return [
            'id' => $type->id,
            'code' => $type->code,
            'name' => TranslationMap::of($type->getTranslations('name')),
            'description' => TranslationMap::of($type->getTranslations('description')),
            'applies_to' => $type->applies_to->value,
            'requires_expiry' => $type->requires_expiry,
            'requires_number' => $type->requires_number,
            'allowed_mimes' => $type->allowed_mimes,
            'max_size_kb' => min($type->max_size_kb, (int) config('provider.documents.max_kilobytes', 10240)),
        ];
    }
}
