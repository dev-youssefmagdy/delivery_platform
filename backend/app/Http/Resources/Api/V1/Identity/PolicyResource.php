<?php

namespace App\Http\Resources\Api\V1\Identity;

use App\Enums\Common\Locale;
use App\Models\PolicyVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A published policy text in the request language (Arabic fallback).
 *
 * @property PolicyVersion $resource
 */
class PolicyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $fallback = Locale::default()->value;

        return [
            'id' => $this->resource->uuid,
            'type' => $this->resource->type->value,
            'version' => $this->resource->version,
            'title' => $this->resource->getTranslation('title', $locale, true) ?: $this->resource->getTranslation('title', $fallback, false),
            'content' => $this->resource->getTranslation('content', $locale, true) ?: $this->resource->getTranslation('content', $fallback, false),
            'published_at' => $this->resource->published_at?->toIso8601ZuluString(),
        ];
    }
}
