<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\DTOs\Location\LocationSample;
use App\Http\Requests\Api\V1\Provider\Concerns\ReadsLocationSamples;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * POST /provider/location: one or a batch of buffered samples.
 */
class StoreLocationRequest extends FormRequest
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
            'samples' => ['required', 'array', 'min:1', 'max:'.(int) config('location.max_batch')],
        ] + $this->sampleRules('samples.*');
    }

    /**
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [fn (Validator $validator) => $this->rejectNullIsland($validator, array_map(
            static fn (int|string $index): string => "samples.{$index}",
            array_keys((array) $this->input('samples', [])),
        ))];
    }

    /**
     * @return list<LocationSample>
     */
    public function samples(): array
    {
        /** @var list<array<string, mixed>> $samples */
        $samples = array_values((array) $this->validated('samples'));

        return array_map(fn (array $sample): LocationSample => $this->sampleFrom($sample), $samples);
    }
}
