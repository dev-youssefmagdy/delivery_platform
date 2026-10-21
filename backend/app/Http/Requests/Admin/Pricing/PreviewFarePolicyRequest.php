<?php

namespace App\Http\Requests\Admin\Pricing;

use App\Models\FarePolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * The "try it" calculator on the policy page: a trip in km / minutes / stops and optional answers. Read-only.
 */
class PreviewFarePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $policy = $this->route('farePolicy');

        return $policy instanceof FarePolicy && Gate::allows('view', $policy);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'distance_km' => ['required', 'numeric', 'min:0', 'max:5000'],
            'duration_min' => ['required', 'numeric', 'min:0', 'max:10000'],
            'stops' => ['nullable', 'integer', 'min:0', 'max:10'],
            'answers' => ['nullable', 'array', 'max:20'],
            'answers.*.key' => ['nullable', 'string', 'max:40'],
            'answers.*.value' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'distance_km' => __('admin.fare_policies.preview.distance'),
            'duration_min' => __('admin.fare_policies.preview.duration'),
            'stops' => __('admin.fare_policies.preview.stops'),
        ];
    }

    public function distanceMeters(): int
    {
        return (int) round(((float) $this->input('distance_km')) * 1000);
    }

    public function durationSeconds(): int
    {
        return (int) round(((float) $this->input('duration_min')) * 60);
    }

    /**
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        $answers = [];

        foreach ((array) $this->input('answers', []) as $row) {
            $key = trim((string) ($row['key'] ?? ''));

            if ($key !== '') {
                $answers[$key] = (string) ($row['value'] ?? '');
            }
        }

        return $answers;
    }
}
