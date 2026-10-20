<?php

namespace App\Http\Requests\Api\V1\Geo;

use App\Http\Requests\Api\V1\Concerns\ValidatesCoordinates;
use App\Support\Geo\GeoPoint;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * GET /places/search?q=&lat=&lng=&limit=   (lat/lng bias the ranking and are optional)
 */
class SearchPlacesRequest extends FormRequest
{
    use ValidatesCoordinates;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:'.(int) config('maps.search.max_query_length', 120)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('maps.search.max_limit', 15)],
        ] + $this->coordinateRules(required: false);
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->rejectImpossiblePoint($validator)];
    }

    public function near(): ?GeoPoint
    {
        return $this->pointFrom();
    }

    public function limit(): ?int
    {
        $limit = $this->validated('limit');

        return $limit === null ? null : (int) $limit;
    }
}
