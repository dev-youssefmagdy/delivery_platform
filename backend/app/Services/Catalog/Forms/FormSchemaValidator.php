<?php

namespace App\Services\Catalog\Forms;

use App\DTOs\Catalog\FormFieldDefinition;
use App\Enums\Catalog\FormFieldType;
use App\Enums\Catalog\VisibilityOperator;
use App\Exceptions\BusinessRuleViolation;

/**
 * Validates a service form schema itself (Plan P1.4 step 2): unique keys, known types, options, ranges and
 * `visible_if` references. Used by the admin FormRequest (inline errors per row) and again by the service, so a
 * schema that is saved or published is always valid regardless of the caller.
 */
final class FormSchemaValidator
{
    public const MAX_FIELDS = 40;

    public const MAX_OPTIONS = 100;

    public const KEY_PATTERN = '/^[a-z][a-z0-9_]{0,39}$/';

    public const OPTION_PATTERN = '/^[A-Za-z0-9_\-]{1,40}$/';

    /**
     * @param array<string, scalar> $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        return (string) __('catalog.schema.'.$key, $replace);
    }

    /**
     * @param array<int|string, mixed> $fields raw list of field arrays (builder input normalised or stored JSON)
     * @return array<string, list<string>> errors keyed `<index>.<property>` (`schema` for list-level errors)
     */
    public function validate(array $fields): array
    {
        $errors = [];

        if (count($fields) > self::MAX_FIELDS) {
            $errors['schema'][] = self::t('too_many_fields', ['max' => self::MAX_FIELDS]);
        }

        $fields = array_values($fields);
        $seen = [];

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                $errors["{$index}.key"][] = self::t('invalid_field');

                continue;
            }

            $this->validateField($index, $field, $seen, $errors);

            if (is_string($field['key'] ?? null)) {
                $seen[] = $field['key'];
            }
        }

        return $errors;
    }

    /**
     * Throws a 422 when the schema is invalid; returns the canonical definitions otherwise.
     *
     * @param array<int|string, mixed> $fields
     * @return list<FormFieldDefinition>
     */
    public function assertValid(array $fields): array
    {
        $errors = $this->validate($fields);

        if ($errors !== []) {
            $first = array_key_first($errors);

            throw BusinessRuleViolation::make('form_schema_invalid', 'fields', message: $errors[$first][0]);
        }

        return array_map(
            static fn (array $field): FormFieldDefinition => FormFieldDefinition::fromArray($field),
            array_values(array_filter($fields, 'is_array')),
        );
    }

    /**
     * @param array<string, mixed> $field
     * @param list<string> $earlierKeys
     * @param array<string, list<string>> $errors
     */
    private function validateField(int $index, array $field, array $earlierKeys, array &$errors): void
    {
        $key = $field['key'] ?? null;

        if (! is_string($key) || preg_match(self::KEY_PATTERN, $key) !== 1) {
            $errors["{$index}.key"][] = self::t('key_invalid');
        } elseif (in_array($key, $earlierKeys, true)) {
            $errors["{$index}.key"][] = self::t('key_duplicate');
        }

        $type = FormFieldType::tryFrom((string) ($field['type'] ?? ''));

        if ($type === null) {
            $errors["{$index}.type"][] = self::t('type_invalid');
        }

        $this->validateLabels($index, $field, $errors);

        if (array_key_exists('required', $field) && $field['required'] !== null && filter_var($field['required'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $errors["{$index}.required"][] = self::t('required_invalid');
        }

        if ($type !== null) {
            $this->validateRange($index, $field, $type, $errors);
            $this->validateOptions($index, $field, $type, $errors);
        }

        $this->validateVisibility($index, $field, $earlierKeys, $errors);
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, list<string>> $errors
     */
    private function validateLabels(int $index, array $field, array &$errors): void
    {
        $label = is_array($field['label'] ?? null) ? $field['label'] : [];

        if (! is_string($label['ar'] ?? null) || trim($label['ar']) === '') {
            $errors["{$index}.label_ar"][] = self::t('label_ar_required');
        }

        foreach (['label' => 191, 'help' => 500] as $property => $max) {
            foreach ((array) ($field[$property] ?? []) as $locale => $text) {
                if (is_string($text) && mb_strlen($text) > $max) {
                    $errors["{$index}.{$property}_{$locale}"][] = self::t('text_too_long', ['max' => $max]);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, list<string>> $errors
     */
    private function validateRange(int $index, array $field, FormFieldType $type, array &$errors): void
    {
        $min = $field['min'] ?? null;
        $max = $field['max'] ?? null;
        $min = $min === '' ? null : $min;
        $max = $max === '' ? null : $max;

        if (($min !== null || $max !== null) && ! $type->supportsRange()) {
            $errors["{$index}.min"][] = self::t('range_not_supported');

            return;
        }

        foreach (['min' => $min, 'max' => $max] as $property => $value) {
            if ($value === null) {
                continue;
            }

            if (! is_numeric($value)) {
                $errors["{$index}.{$property}"][] = self::t('range_numeric');
            } elseif ($type !== FormFieldType::Integer && $type !== FormFieldType::Decimal && (float) $value < 0) {
                $errors["{$index}.{$property}"][] = self::t('range_negative');
            } elseif (! $type->isNumeric() && $type !== FormFieldType::Dimensions && floor((float) $value) !== (float) $value) {
                $errors["{$index}.{$property}"][] = self::t('range_integer');
            } elseif ($type === FormFieldType::Integer && floor((float) $value) !== (float) $value) {
                $errors["{$index}.{$property}"][] = self::t('range_integer');
            }
        }

        if (is_numeric($min) && is_numeric($max) && (float) $min > (float) $max) {
            $errors["{$index}.max"][] = self::t('range_order');
        }
    }

    /**
     * @param array<string, mixed> $field
     * @param array<string, list<string>> $errors
     */
    private function validateOptions(int $index, array $field, FormFieldType $type, array &$errors): void
    {
        $options = $field['options'] ?? [];

        if (! is_array($options)) {
            $errors["{$index}.options"][] = self::t('options_invalid');

            return;
        }

        if (! $type->usesOptions()) {
            if ($options !== []) {
                $errors["{$index}.options"][] = self::t('options_not_allowed');
            }

            return;
        }

        if ($options === []) {
            $errors["{$index}.options"][] = self::t('options_required');

            return;
        }

        if (count($options) > self::MAX_OPTIONS) {
            $errors["{$index}.options"][] = self::t('options_too_many', ['max' => self::MAX_OPTIONS]);

            return;
        }

        $values = [];

        foreach (array_values($options) as $position => $option) {
            $value = is_array($option) ? ($option['value'] ?? null) : null;
            $label = is_array($option) && is_array($option['label'] ?? null) ? ($option['label']['ar'] ?? null) : null;
            $line = $position + 1;

            if (! is_string($value) || preg_match(self::OPTION_PATTERN, $value) !== 1) {
                $errors["{$index}.options"][] = self::t('option_value_invalid', ['line' => $line]);
            } elseif (in_array($value, $values, true)) {
                $errors["{$index}.options"][] = self::t('option_duplicate', ['line' => $line]);
            } else {
                $values[] = $value;
            }

            if (! is_string($label) || trim($label) === '') {
                $errors["{$index}.options"][] = self::t('option_label_required', ['line' => $line]);
            }
        }
    }

    /**
     * @param array<string, mixed> $field
     * @param list<string> $earlierKeys
     * @param array<string, list<string>> $errors
     */
    private function validateVisibility(int $index, array $field, array $earlierKeys, array &$errors): void
    {
        $rule = $field['visible_if'] ?? null;

        if ($rule === null || $rule === []) {
            return;
        }

        if (! is_array($rule)) {
            $errors["{$index}.visible_if"][] = self::t('visible_invalid');

            return;
        }

        $operator = VisibilityOperator::tryFrom((string) ($rule['op'] ?? ''));
        $target = $rule['field'] ?? null;

        if (! is_string($target) || ! in_array($target, $earlierKeys, true)) {
            $errors["{$index}.visible_if"][] = self::t('visible_field_unknown');
        }

        if ($operator === null) {
            $errors["{$index}.visible_if"][] = self::t('visible_operator_invalid');

            return;
        }

        if (! $operator->needsValue()) {
            return;
        }

        $value = $rule['value'] ?? null;

        if (in_array($operator, [VisibilityOperator::In, VisibilityOperator::NotIn], true)) {
            if (! is_array($value) || $value === [] || array_filter($value, static fn (mixed $item): bool => ! is_scalar($item)) !== []) {
                $errors["{$index}.visible_if"][] = self::t('visible_list_required');
            }
        } elseif (! is_scalar($value) || $value === '') {
            $errors["{$index}.visible_if"][] = self::t('visible_value_required');
        } elseif (in_array($operator, [VisibilityOperator::GreaterThan, VisibilityOperator::GreaterOrEqual, VisibilityOperator::LessThan, VisibilityOperator::LessOrEqual], true) && ! is_numeric($value)) {
            $errors["{$index}.visible_if"][] = self::t('visible_number_required');
        }
    }
}
