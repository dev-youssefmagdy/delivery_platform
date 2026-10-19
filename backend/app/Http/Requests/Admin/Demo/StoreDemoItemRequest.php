<?php

namespace App\Http\Requests\Admin\Demo;

use App\Enums\Demo\DemoItemStatus;
use App\Enums\Demo\DemoItemTag;
use App\Enums\Demo\DemoItemType;
use App\Models\DemoItem;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreDemoItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', DemoItem::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $current = $this->route('demoItem');

        return [
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:191'],
            'name.en' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'code' => [
                'required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('demo_items', 'code')->ignore($current instanceof DemoItem ? $current->id : null),
            ],
            'type' => ['required', Rule::enum(DemoItemType::class)],
            'status' => ['required', Rule::enum(DemoItemStatus::class)->only(DemoItemStatus::editable())],
            'price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'tags' => ['nullable', 'array', 'max:4'],
            'tags.*' => ['distinct', Rule::enum(DemoItemTag::class)],
            'is_featured' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'available_on' => ['nullable', 'date_format:Y-m-d'],
            'published_at' => ['nullable', 'date_format:Y-m-d H:i'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'cover' => ['nullable', 'file', 'image', 'mimes:'.implode(',', (array) config('admin.uploads.image_mimes')), 'max:'.config('admin.uploads.image_max_kb')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'area' => ['nullable', 'string', 'json', $this->polygonRule()],
            'specs' => ['nullable', 'array', 'max:50'],
            'specs.*.key' => ['required', 'string', 'max:100', 'distinct'],
            'specs.*.value' => ['nullable', 'string', 'max:500'],
            'options' => ['nullable', 'array', 'max:20'],
            'options.*.label' => ['required', 'string', 'max:100'],
            'options.*.price' => ['nullable', 'integer', 'min:0'],
            'options.*.is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'quick' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $field = static fn (string $key): string => (string) __("admin.demo_items.fields.{$key}");

        return [
            'name.ar' => $field('name').' (ar)',
            'name.en' => $field('name').' (en)',
            'description.ar' => $field('description').' (ar)',
            'description.en' => $field('description').' (en)',
            'code' => $field('code'),
            'type' => $field('type'),
            'status' => $field('status'),
            'price' => $field('price'),
            'country_id' => $field('country'),
            'tags' => $field('tags'),
            'tags.*' => $field('tags'),
            'available_on' => $field('available_on'),
            'published_at' => $field('published_at'),
            'starts_on' => $field('starts_on'),
            'ends_on' => $field('ends_on'),
            'cover' => $field('cover'),
            'latitude' => __('admin.form.lat'),
            'longitude' => __('admin.form.lng'),
            'area' => $field('area'),
            'specs.*.key' => __('admin.form.key'),
            'specs.*.value' => __('admin.form.value'),
            'options.*.label' => $field('option_label'),
            'options.*.price' => $field('option_price'),
            'notes' => $field('notes'),
        ];
    }

    /**
     * A JSON list of at least 3 [lat, lng] points.
     */
    private function polygonRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            $points = is_string($value) ? json_decode($value, true) : null;

            if (! is_array($points) || count($points) < 3) {
                $fail(__('admin.demo_items.validation.area'));

                return;
            }

            foreach ($points as $point) {
                if (! is_array($point) || count($point) !== 2 || ! is_numeric($point[0] ?? null) || ! is_numeric($point[1] ?? null)
                    || abs((float) $point[0]) > 90 || abs((float) $point[1]) > 180) {
                    $fail(__('admin.demo_items.validation.area'));

                    return;
                }
            }
        };
    }
}
