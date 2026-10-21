<?php

namespace App\Http\Requests\Api\V1\Trip;

use App\DTOs\Booking\RatingData;
use App\Enums\Common\ActorType;
use App\Enums\Reviews\RatingTag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /bookings/{booking}/rating (customer rates the provider) and POST /provider/bookings/{booking}/rating (provider
 * rates the customer): 1-5 stars, optional tags of the ratee's list, optional private comment.
 */
class RateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ratee = $this->routeIs('api.v1.provider.*') ? ActorType::Customer : ActorType::Provider;

        return [
            'stars' => ['required', 'integer', 'between:1,5'],
            'tags' => ['nullable', 'array', 'max:6'],
            'tags.*' => ['string', Rule::in(RatingTag::valuesForRatee($ratee))],
            'comment' => ['nullable', 'string', 'max:'.(int) config('booking.trip.comment_max_chars', 1000)],
        ];
    }

    public function toDto(): RatingData
    {
        return new RatingData(
            stars: $this->integer('stars'),
            tags: array_values(array_map('strval', (array) $this->input('tags', []))),
            comment: $this->filled('comment') ? (string) $this->input('comment') : null,
        );
    }
}
