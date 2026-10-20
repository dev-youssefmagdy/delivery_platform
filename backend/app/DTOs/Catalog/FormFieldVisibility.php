<?php

namespace App\DTOs\Catalog;

use App\Enums\Catalog\VisibilityOperator;

/**
 * `visible_if` condition: the field is shown (and validated) only while `field` satisfies `op` / `value`.
 */
final readonly class FormFieldVisibility
{
    public function __construct(public string $field, public VisibilityOperator $operator, public mixed $value = null) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        $operator = VisibilityOperator::tryFrom((string) ($data['op'] ?? ''));

        if (! is_string($data['field'] ?? null) || $data['field'] === '' || $operator === null) {
            return null;
        }

        return new self($data['field'], $operator, $operator->needsValue() ? ($data['value'] ?? null) : null);
    }

    /**
     * @param array<string, mixed> $values answers keyed by field key (hidden fields already removed)
     */
    public function isSatisfiedBy(array $values): bool
    {
        return $this->operator->matches($values[$this->field] ?? null, $this->value);
    }

    /**
     * @return array{field: string, op: string, value?: mixed}
     */
    public function toArray(): array
    {
        return $this->operator->needsValue()
            ? ['field' => $this->field, 'op' => $this->operator->value, 'value' => $this->value]
            : ['field' => $this->field, 'op' => $this->operator->value];
    }
}
