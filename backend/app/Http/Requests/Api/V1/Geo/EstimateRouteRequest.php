<?php

namespace App\Http\Requests\Api\V1\Geo;

use App\Http\Requests\Api\V1\Concerns\ValidatesCoordinates;
use App\Support\Geo\GeoPoint;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use LogicException;

/**
 * GET /routes/estimate?origin=lat,lng&destination=lat,lng&stops[]=lat,lng&service_category_id=
 */
class EstimateRouteRequest extends FormRequest
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
            'origin' => ['required', 'string', 'max:40'],
            'destination' => ['required', 'string', 'max:40'],
            'stops' => ['nullable', 'array', 'max:'.(int) config('maps.route.max_stops', 5)],
            'stops.*' => ['string', 'max:40'],
            'service_category_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (['origin', 'destination'] as $key) {
                if (! $validator->errors()->has($key) && $this->parsedPoint($key) === null) {
                    $validator->errors()->add($key, __('validation.coordinates_pair'));
                }
            }

            foreach ((array) $this->input('stops', []) as $index => $stop) {
                if (! is_string($stop) || GeoPoint::parse($stop) === null) {
                    $validator->errors()->add("stops.{$index}", __('validation.coordinates_pair'));
                }
            }
        }];
    }

    public function origin(): GeoPoint
    {
        return $this->parsedPoint('origin') ?? throw new LogicException('origin was validated');
    }

    public function destination(): GeoPoint
    {
        return $this->parsedPoint('destination') ?? throw new LogicException('destination was validated');
    }

    /**
     * @return list<GeoPoint>
     */
    public function stops(): array
    {
        $stops = [];

        foreach ((array) $this->validated('stops', []) as $stop) {
            $point = is_string($stop) ? GeoPoint::parse($stop) : null;

            if ($point !== null) {
                $stops[] = $point;
            }
        }

        return $stops;
    }

    public function serviceCategoryId(): ?int
    {
        $id = $this->validated('service_category_id');

        return $id === null ? null : (int) $id;
    }
}
