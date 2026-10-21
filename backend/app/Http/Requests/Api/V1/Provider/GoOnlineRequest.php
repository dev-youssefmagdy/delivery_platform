<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\DTOs\Location\LocationSample;
use App\Http\Requests\Api\V1\Provider\Concerns\ReadsLocationSamples;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * POST /provider/availability/online: the vehicle to work with (optional) and the current position, so the
 * freshness check has something to look at.
 */
class GoOnlineRequest extends FormRequest
{
    use ReadsLocationSamples;

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
            'vehicle_id' => ['nullable', 'uuid'],
            'location' => ['required', 'array'],
        ] + $this->sampleRules('location');
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->rejectNullIsland($validator, ['location'])];
    }

    public function vehicleUuid(): ?string
    {
        $value = $this->validated('vehicle_id');

        return is_string($value) ? $value : null;
    }

    public function sample(): LocationSample
    {
        /** @var array<string, mixed> $location */
        $location = $this->validated('location');

        return $this->sampleFrom($location);
    }
}
