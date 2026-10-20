<?php

namespace App\Http\Resources\Api\V1\Catalog;

use App\Models\ServiceFamily;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service family shown on the customer home (names are returned in both locales; the app picks one).
 *
 * @property ServiceFamily $resource
 */
class ServiceFamilyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $family = $this->resource;

        return [
            'id' => $family->id,
            'code' => $family->code,
            'kind' => $family->kind->value,
            'name' => TranslationMap::of($family->getTranslations('name')),
            'description' => TranslationMap::of($family->getTranslations('description')),
            'icon' => $family->icon,
            'sort_order' => $family->sort_order,
            'categories_count' => (int) ($family->getAttribute('public_categories_count') ?? 0),
        ];
    }
}
