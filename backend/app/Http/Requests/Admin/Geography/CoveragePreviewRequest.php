<?php

namespace App\Http\Requests\Admin\Geography;

use App\Enums\Admin\AdminPermission;
use App\Support\Geo\GeoPoint;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * POST /coverage-preview: one point, or two for a route estimate.
 */
class CoveragePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(AdminPermission::ServiceAreasManage->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'origin_lat' => ['required', 'numeric', 'between:-90,90'],
            'origin_lng' => ['required', 'numeric', 'between:-180,180'],
            'destination_lat' => ['nullable', 'required_with:destination_lng', 'numeric', 'between:-90,90'],
            'destination_lng' => ['nullable', 'required_with:destination_lat', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (['origin', 'destination'] as $prefix) {
                $keys = ["{$prefix}_lat", "{$prefix}_lng"];

                if ($this->filled($keys[0]) && $this->filled($keys[1]) && ! $validator->errors()->hasAny($keys)
                    && GeoPoint::tryMake($this->input($keys[0]), $this->input($keys[1])) === null) {
                    $validator->errors()->add($keys[0], __('validation.coordinates'));
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'origin_lat' => __('admin.coverage.origin').' - '.__('admin.form.lat'),
            'origin_lng' => __('admin.coverage.origin').' - '.__('admin.form.lng'),
            'destination_lat' => __('admin.coverage.destination').' - '.__('admin.form.lat'),
            'destination_lng' => __('admin.coverage.destination').' - '.__('admin.form.lng'),
        ];
    }

    public function origin(): GeoPoint
    {
        return GeoPoint::make($this->validated('origin_lat'), $this->validated('origin_lng'));
    }

    public function destination(): ?GeoPoint
    {
        return $this->filled('destination_lat') ? GeoPoint::tryMake($this->validated('destination_lat'), $this->validated('destination_lng')) : null;
    }
}
