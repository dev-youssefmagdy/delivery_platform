<?php

namespace App\Services\Catalog\Forms;

use App\DTOs\Catalog\FormFieldDefinition;
use App\Enums\Catalog\FormFieldType;
use App\Support\Auth\PhoneNumber;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Compiles a published form schema into Laravel rules for a booking's `service_details` and validates the
 * answers (Plan P1.4 step 2). Unknown keys are rejected; fields whose `visible_if` is not satisfied are ignored
 * (dropped from the result, never required); required fields are enforced only while visible.
 */
final class ServiceFormValidator
{
    public const ATTRIBUTE = 'service_details';

    public const DEFAULT_TEXT_MAX = 255;

    public const DEFAULT_TEXTAREA_MAX = 2000;

    public const DEFAULT_PHOTO_MAX = 10;

    public const DATETIME_FORMAT = 'Y-m-d\TH:i';

    /**
     * Validates and returns the visible answers, normalised to the schema (only known, visible keys).
     *
     * @param list<FormFieldDefinition|array<string, mixed>> $schema
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     *
     * @throws ValidationException errors keyed `service_details.<field>[.<sub>]`
     */
    public function validate(array $schema, array $details, string $attribute = self::ATTRIBUTE): array
    {
        $fields = $this->definitions($schema);
        $visible = $this->visibleFields($fields, $details);
        $rules = [];
        $labels = [];

        foreach ($visible as $field) {
            foreach ($this->rulesFor($field) as $path => $fieldRules) {
                $rules[$attribute.'.'.$path] = $fieldRules;
            }

            $labels[$attribute.'.'.$field->key] = $field->label['ar'] ?? $field->key;
        }

        $validator = Validator::make([$attribute => $details], $rules, [], $labels);

        $validator->after(function ($validator) use ($fields, $details, $attribute): void {
            $known = array_map(static fn (FormFieldDefinition $field): string => $field->key, $fields);

            foreach (array_keys($details) as $key) {
                if (! in_array((string) $key, $known, true)) {
                    $validator->errors()->add($attribute.'.'.$key, (string) __('catalog.details.unknown_key', ['key' => $key]));
                }
            }
        });

        $validated = $validator->validate();
        $answers = (array) ($validated[$attribute] ?? []);

        $result = [];

        foreach ($visible as $field) {
            if (array_key_exists($field->key, $answers)) {
                $result[$field->key] = $answers[$field->key];
            }
        }

        return $result;
    }

    /**
     * The compiled rules for the visible fields given the current answers (the flat Laravel rule map).
     *
     * @param list<FormFieldDefinition|array<string, mixed>> $schema
     * @param array<string, mixed> $details
     * @return array<string, list<mixed>>
     */
    public function rules(array $schema, array $details = [], string $attribute = self::ATTRIBUTE): array
    {
        $rules = [];

        foreach ($this->visibleFields($this->definitions($schema), $details) as $field) {
            foreach ($this->rulesFor($field) as $path => $fieldRules) {
                $rules[$attribute.'.'.$path] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Fields that are visible for the given answers, in schema order. A field depending on a hidden field is
     * evaluated against "no answer", exactly like the Flutter renderer.
     *
     * @param list<FormFieldDefinition> $fields
     * @param array<string, mixed> $details
     * @return list<FormFieldDefinition>
     */
    public function visibleFields(array $fields, array $details): array
    {
        $visible = [];
        $answers = [];

        foreach ($fields as $field) {
            if ($field->visibleIf !== null && ! $field->visibleIf->isSatisfiedBy($answers)) {
                continue;
            }

            $visible[] = $field;

            if (array_key_exists($field->key, $details)) {
                $answers[$field->key] = $details[$field->key];
            }
        }

        return $visible;
    }

    /**
     * @param list<FormFieldDefinition|array<string, mixed>> $schema
     * @return list<FormFieldDefinition>
     */
    private function definitions(array $schema): array
    {
        return array_map(
            static fn (FormFieldDefinition|array $field): FormFieldDefinition => $field instanceof FormFieldDefinition ? $field : FormFieldDefinition::fromArray($field),
            $schema,
        );
    }

    /**
     * @return array<string, list<mixed>> rule map keyed by the path under the details attribute
     */
    private function rulesFor(FormFieldDefinition $field): array
    {
        $base = [$field->required ? 'required' : 'nullable'];
        $key = $field->key;
        $min = $field->min;
        $max = $field->max;

        return match ($field->type) {
            FormFieldType::Text => [$key => [...$base, 'string', 'min:'.(int) ($min ?? 0), 'max:'.(int) ($max ?? self::DEFAULT_TEXT_MAX)]],
            FormFieldType::Textarea => [$key => [...$base, 'string', 'min:'.(int) ($min ?? 0), 'max:'.(int) ($max ?? self::DEFAULT_TEXTAREA_MAX)]],
            FormFieldType::Integer => [$key => [...$base, 'integer', ...$this->numberBounds($min, $max)]],
            FormFieldType::Decimal => [$key => [...$base, 'numeric', ...$this->numberBounds($min, $max)]],
            FormFieldType::Weight => [$key => [...$base, 'numeric', ...$this->numberBounds($min ?? 0.01, $max)]],
            FormFieldType::Boolean => [$key => [...$base, 'boolean']],
            FormFieldType::Select => [$key => [...$base, Rule::in($field->optionValues())]],
            FormFieldType::Multiselect => [
                $key => [...$base, 'array', 'min:'.(int) ($min ?? 0), 'max:'.(int) ($max ?? count($field->options))],
                $key.'.*' => ['distinct', Rule::in($field->optionValues())],
            ],
            FormFieldType::Date => [$key => [...$base, 'date_format:Y-m-d']],
            FormFieldType::Time => [$key => [...$base, 'date_format:H:i']],
            FormFieldType::Datetime => [$key => [...$base, 'date_format:'.self::DATETIME_FORMAT]],
            FormFieldType::Contact => [
                $key => [...$base, 'array', 'required_array_keys:name,phone'],
                $key.'.name' => ['sometimes', 'filled', 'string', 'max:100'],
                $key.'.phone' => ['sometimes', 'filled', 'string', $this->phoneRule()],
            ],
            FormFieldType::Photo => [
                $key => [...$base, 'array', 'min:'.(int) ($min ?? 0), 'max:'.(int) ($max ?? self::DEFAULT_PHOTO_MAX)],
                // Each reference is the uuid of an uploaded booking attachment (P4.2).
                $key.'.*' => ['uuid', 'distinct'],
            ],
            FormFieldType::ItemChecklist => [
                $key => [...$base, 'array', 'min:'.(int) ($min ?? 0), 'max:'.(int) ($max ?? count($field->options))],
                $key.'.*.item' => ['required', 'string', 'distinct', Rule::in($field->optionValues())],
                $key.'.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
                // Optional per-item facts (P4.2): note, weight and dimensions in cm.
                $key.'.*.note' => ['nullable', 'string', 'max:255'],
                $key.'.*.weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
                $key.'.*.dimensions' => ['nullable', 'array', 'required_array_keys:length,width,height'],
                $key.'.*.dimensions.length' => ['numeric', 'min:0.01', 'max:2000'],
                $key.'.*.dimensions.width' => ['numeric', 'min:0.01', 'max:2000'],
                $key.'.*.dimensions.height' => ['numeric', 'min:0.01', 'max:2000'],
            ],
            FormFieldType::Dimensions => [
                $key => [...$base, 'array', 'required_array_keys:length,width,height'],
                ...array_combine(
                    array_map(static fn (string $side): string => $key.'.'.$side, ['length', 'width', 'height']),
                    array_fill(0, 3, ['sometimes', 'numeric', ...$this->numberBounds($min ?? 0.01, $max)]),
                ),
            ],
        };
    }

    /**
     * @return list<string>
     */
    private function numberBounds(int|float|null $min, int|float|null $max): array
    {
        return array_values(array_filter([
            $min === null ? null : 'min:'.$min,
            $max === null ? null : 'max:'.$max,
        ]));
    }

    private function phoneRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || PhoneNumber::normalize($value) === null) {
                $fail((string) __('catalog.details.phone_invalid'));
            }
        };
    }
}
