<?php

namespace App\DTOs\Catalog;

/**
 * One choice of a select / multiselect / item_checklist field.
 */
final readonly class FormFieldOption
{
    /**
     * @param array<string, string> $label translations keyed by locale (ar required)
     */
    public function __construct(public string $value, public array $label) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(trim((string) ($data['value'] ?? '')), FormFieldDefinition::translations($data['label'] ?? []));
    }

    /**
     * @return array{value: string, label: array<string, string>}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->label];
    }
}
