<?php

namespace App\Services\Catalog\Forms;

use App\DTOs\Catalog\FormFieldDefinition;
use App\Enums\Catalog\FormFieldType;
use App\Enums\Catalog\VisibilityOperator;

/**
 * Converts between the admin builder's flat repeater rows (`fields[i][label_ar]`, `options_text` ...) and the
 * canonical field arrays stored in `service_form_schemas.fields`. Output of fromBuilder() is deliberately NOT
 * validated; FormSchemaValidator decides.
 */
final class FormSchemaBuilderMapper
{
    /**
     * @param array<int|string, mixed> $rows
     * @return list<array<string, mixed>>
     */
    public static function fromBuilder(array $rows): array
    {
        $fields = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = FormFieldType::tryFrom((string) ($row['type'] ?? ''));
            $operator = VisibilityOperator::tryFrom((string) ($row['visible_op'] ?? ''));
            $visibleField = trim((string) ($row['visible_field'] ?? ''));

            $fields[] = [
                'key' => trim((string) ($row['key'] ?? '')),
                'type' => (string) ($row['type'] ?? ''),
                'label' => ['ar' => trim((string) ($row['label_ar'] ?? '')), 'en' => trim((string) ($row['label_en'] ?? ''))],
                'help' => ['ar' => trim((string) ($row['help_ar'] ?? '')), 'en' => trim((string) ($row['help_en'] ?? ''))],
                'required' => filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'min' => self::nullableNumber($row['min'] ?? null),
                'max' => self::nullableNumber($row['max'] ?? null),
                'options' => $type?->usesOptions() === true ? self::parseOptions((string) ($row['options_text'] ?? '')) : self::rawOptions($row, $type),
                'visible_if' => $visibleField === '' && $operator === null ? null : [
                    'field' => $visibleField,
                    'op' => (string) ($row['visible_op'] ?? ''),
                    'value' => self::parseVisibilityValue($operator, (string) ($row['visible_value'] ?? '')),
                ],
            ];
        }

        // Normalise empty translations away (help is optional, label.en is optional).
        return array_map(static function (array $field): array {
            $field['label'] = FormFieldDefinition::translations($field['label']) + ['ar' => ''];
            $field['help'] = FormFieldDefinition::translations($field['help']);

            return $field;
        }, $fields);
    }

    /**
     * Canonical stored fields to builder rows (for rendering and for re-rendering after a failed submit).
     *
     * @param array<int|string, mixed> $fields
     * @return list<array<string, mixed>>
     */
    public static function toBuilder(array $fields): array
    {
        $rows = [];

        foreach ($fields as $stored) {
            if (! is_array($stored)) {
                continue;
            }

            $field = FormFieldDefinition::fromArray($stored);
            $visibility = $field->visibleIf;

            $rows[] = [
                'key' => $field->key,
                'type' => $field->type->value,
                'label_ar' => $field->label['ar'] ?? '',
                'label_en' => $field->label['en'] ?? '',
                'help_ar' => $field->help['ar'] ?? '',
                'help_en' => $field->help['en'] ?? '',
                'required' => $field->required,
                'min' => $field->min,
                'max' => $field->max,
                'options_text' => implode("\n", array_map(
                    static fn ($option): string => implode('|', array_filter([$option->value, $option->label['ar'] ?? '', $option->label['en'] ?? ''], static fn (string $part): bool => $part !== '')),
                    $field->options,
                )),
                'visible_field' => $visibility?->field,
                'visible_op' => $visibility?->operator->value,
                'visible_value' => self::visibilityValueToText($visibility?->value),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{value: string, label: array<string, string>}>
     */
    private static function parseOptions(string $text): array
    {
        $options = [];

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 3));
            $options[] = [
                'value' => $parts[0],
                'label' => FormFieldDefinition::translations(['ar' => $parts[1] ?? '', 'en' => $parts[2] ?? '']) + ['ar' => ''],
            ];
        }

        return $options;
    }

    /**
     * Types that do not use options must not carry any; a non-empty text is surfaced as an error by the validator.
     *
     * @param array<string, mixed> $row
     * @return list<array{value: string, label: array<string, string>}>
     */
    private static function rawOptions(array $row, ?FormFieldType $type): array
    {
        $text = trim((string) ($row['options_text'] ?? ''));

        return $type === null || $text === '' ? [] : self::parseOptions($text);
    }

    private static function nullableNumber(mixed $value): int|float|string|null
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value) ? $value + 0 : (string) $value;
    }

    private static function parseVisibilityValue(?VisibilityOperator $operator, string $text): mixed
    {
        $text = trim($text);

        if ($operator === null || ! $operator->needsValue() || $text === '') {
            return null;
        }

        if ($operator === VisibilityOperator::In || $operator === VisibilityOperator::NotIn) {
            return array_values(array_filter(array_map(static fn (string $part): mixed => self::scalar(trim($part)), explode(',', $text)), static fn (mixed $part): bool => $part !== ''));
        }

        return self::scalar($text);
    }

    private static function scalar(string $text): mixed
    {
        return match (true) {
            $text === 'true' => true,
            $text === 'false' => false,
            is_numeric($text) => $text + 0,
            default => $text,
        };
    }

    private static function visibilityValueToText(mixed $value): string
    {
        return match (true) {
            is_array($value) => implode(',', array_map(static fn (mixed $item): string => self::visibilityValueToText($item), $value)),
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => '',
            default => (string) $value,
        };
    }
}
