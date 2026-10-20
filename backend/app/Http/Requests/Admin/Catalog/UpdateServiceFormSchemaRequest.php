<?php

namespace App\Http\Requests\Admin\Catalog;

use App\Enums\Catalog\FormFieldType;
use App\Enums\Catalog\VisibilityOperator;
use App\Models\ServiceCategory;
use App\Services\Catalog\Forms\FormSchemaBuilderMapper;
use App\Services\Catalog\Forms\FormSchemaValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * The form-schema builder. Row-shape rules give inline errors per repeater cell; FormSchemaValidator adds the
 * cross-row rules (unique keys, options, `visible_if` references) and its errors are mapped back to the cells.
 */
class UpdateServiceFormSchemaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('serviceCategory');

        return $category instanceof ServiceCategory && Gate::allows('update', $category);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'fields' => ['nullable', 'array', 'max:'.FormSchemaValidator::MAX_FIELDS],
            'fields.*.key' => ['required', 'string', 'max:40'],
            'fields.*.type' => ['required', Rule::enum(FormFieldType::class)],
            'fields.*.label_ar' => ['required', 'string', 'max:191'],
            'fields.*.label_en' => ['nullable', 'string', 'max:191'],
            'fields.*.help_ar' => ['nullable', 'string', 'max:500'],
            'fields.*.help_en' => ['nullable', 'string', 'max:500'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.min' => ['nullable', 'numeric'],
            'fields.*.max' => ['nullable', 'numeric'],
            'fields.*.options_text' => ['nullable', 'string', 'max:10000'],
            'fields.*.visible_field' => ['nullable', 'string', 'max:40'],
            'fields.*.visible_op' => ['nullable', Rule::enum(VisibilityOperator::class)],
            'fields.*.visible_value' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rows = array_values((array) $this->input('fields', []));
            $indexes = array_keys((array) $this->input('fields', []));
            $errors = app(FormSchemaValidator::class)->validate(FormSchemaBuilderMapper::fromBuilder($rows));

            foreach ($errors as $path => $messages) {
                [$position, $property] = array_pad(explode('.', $path, 2), 2, 'key');
                $row = $indexes[(int) $position] ?? $position;
                $cell = match ($property) {
                    'label_ar', 'label_en', 'help_ar', 'help_en' => $property,
                    'visible_if' => 'visible_field',
                    'options' => 'options_text',
                    default => $property,
                };

                foreach ($messages as $message) {
                    $validator->errors()->add($path === 'schema' ? 'fields' : "fields.{$row}.{$cell}", $message);
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $f = static fn (string $key): string => (string) __("admin.service_categories.schema.$key");

        return [
            'fields' => $f('title'),
            'fields.*.key' => $f('key'),
            'fields.*.type' => $f('type'),
            'fields.*.label_ar' => $f('label_ar'),
            'fields.*.label_en' => $f('label_en'),
            'fields.*.help_ar' => $f('help_ar'),
            'fields.*.help_en' => $f('help_en'),
            'fields.*.required' => $f('required'),
            'fields.*.min' => $f('min'),
            'fields.*.max' => $f('max'),
            'fields.*.options_text' => $f('options'),
            'fields.*.visible_field' => $f('visible_field'),
            'fields.*.visible_op' => $f('visible_op'),
            'fields.*.visible_value' => $f('visible_value'),
        ];
    }
}
