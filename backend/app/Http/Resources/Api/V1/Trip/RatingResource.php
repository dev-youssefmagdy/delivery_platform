<?php

namespace App\Http\Resources\Api\V1\Trip;

use App\Enums\Reviews\RatingTag;
use App\Models\BookingRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property BookingRating $resource
 */
class RatingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rating = $this->resource;

        return [
            'id' => $rating->uuid,
            'stars' => $rating->stars,
            'tags' => array_map(static fn (string $tag): array => ['code' => $tag, 'label' => RatingTag::tryFrom($tag)?->label() ?? $tag], (array) ($rating->tags ?? [])),
            'comment' => $rating->comment,
            // The writer sees whether moderation counts the review (visible / hidden / under_review) and can appeal a hidden one.
            'moderation_status' => $rating->moderation_status->value,
            'created_at' => $rating->created_at->toIso8601ZuluString(),
        ];
    }
}
