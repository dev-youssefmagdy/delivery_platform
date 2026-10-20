<?php

namespace App\Http\Requests\Api\V1\Geo;

use App\Http\Requests\Api\V1\Concerns\ValidatesCoordinates;
use App\Support\Geo\GeoPoint;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * GET /service-areas/resolve?lat=&lng=
 */
class ResolveAreaRequest extends FormRequest
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
        return $this->coordinateRules();
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->rejectImpossiblePoint($validator)];
    }

    public function point(): GeoPoint
    {
        return GeoPoint::make($this->validated('lat'), $this->validated('lng'));
    }
}
