<?php

namespace App\Http\Requests\Api\V1\Geo;

use App\DTOs\Geography\SavedPlaceData;
use App\Enums\Geo\LocationPrecision;
use App\Enums\Geo\SavedPlaceType;
use App\Http\Requests\Api\V1\Concerns\ValidatesCoordinates;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * POST /me/saved-places and PUT /me/saved-places/{uuid}.
 */
class SavedPlaceRequest extends FormRequest
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
            'label' => ['required', 'string', 'min:1', 'max:60'],
            'type' => ['required', 'string', Rule::enum(SavedPlaceType::class)],
            'address' => ['nullable', 'array'],
            'address.title' => ['nullable', 'string', 'max:191'],
            'address.line' => ['nullable', 'string', 'max:500'],
            'address.precision' => ['nullable', 'string', Rule::enum(LocationPrecision::class)],
            'address.provider' => ['nullable', 'string', 'max:40'],
        ] + $this->coordinateRules();
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->rejectImpossiblePoint($validator)];
    }

    public function toDto(): SavedPlaceData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return SavedPlaceData::fromValidated($validated);
    }
}
