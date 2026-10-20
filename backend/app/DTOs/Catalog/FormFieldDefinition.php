<?php

namespace App\DTOs\Catalog;

use App\Enums\Catalog\FormFieldType;

/**
 * Canonical form-schema field (Plan §2.3, docs/API.md 8.6). Stored as JSON in `service_form_schemas.fields`,
 * served to the apps unchanged and compiled to rules by ServiceFormValidator.
 */
final readonly class FormFieldDefinition
{
    /**
     * @param array<string, string> $label
     * @param array<string, string> $help
     * @param list<FormFieldOption> $options
     */
    public function __construct(
        public string $key,
        public FormFieldType $type,
        public array $label,
        public array $help = [],
        public bool $required = false,
        public int|float|null $min = null,
        public int|float|null $max = null,
        public array $options = [],
        public ?FormFieldVisibility $visibleIf = null,
    ) {}

    /**
     * Lenient: callers validate with FormSchemaValidator first. Unknown types fall back to text so reading an
     * old row can never throw.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $options = [];

        foreach ((array) ($data['options'] ?? []) as $option) {
            if (is_array($option)) {
                $options[] = FormFieldOption::fromArray($option);
            }
        }

        $visibility = is_array($data['visible_if'] ?? null) ? FormFieldVisibility::fromArray($data['visible_if']) : null;

        return new self(
            key: trim((string) ($data['key'] ?? '')),
            type: FormFieldType::tryFrom((string) ($data['type'] ?? '')) ?? FormFieldType::Text,
            label: self::translations($data['label'] ?? []),
            help: self::translations($data['help'] ?? []),
            required: filter_var($data['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            min: self::number($data['min'] ?? null),
            max: self::number($data['max'] ?? null),
            options: $options,
            visibleIf: $visibility,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type->value,
            'label' => $this->label,
            'help' => $this->help === [] ? null : $this->help,
            'required' => $this->required,
            'min' => $this->min,
            'max' => $this->max,
            'options' => array_map(static fn (FormFieldOption $option): array => $option->toArray(), $this->options),
            'visible_if' => $this->visibleIf?->toArray(),
        ];
    }

    /**
     * @return list<string>
     */
    public function optionValues(): array
    {
        return array_map(static fn (FormFieldOption $option): string => $option->value, $this->options);
    }

    /**
     * Non-empty, trimmed translations only.
     *
     * @return array<string, string>
     */
    public static function translations(mixed $raw): array
    {
        $result = [];

        foreach ((array) $raw as $locale => $text) {
            if (is_string($locale) && is_scalar($text) && trim((string) $text) !== '') {
                $result[$locale] = trim((string) $text);
            }
        }

        return $result;
    }

    private static function number(mixed $value): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        $number = $value + 0;

        return is_float($number) && floor($number) === $number && abs($number) < 1e15 ? (int) $number : $number;
    }
}
