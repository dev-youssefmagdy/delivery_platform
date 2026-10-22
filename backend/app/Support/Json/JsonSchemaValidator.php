<?php

namespace App\Support\Json;

/**
 * A small, dependency-free JSON Schema (draft-07 subset) validator for stored, versioned documents such as an offer's
 * scope: type, enum, minimum / maximum, maxLength, maxItems, uniqueItems, required, properties, items and
 * additionalProperties. It returns every violation as `path => rule` so callers can map them to field errors.
 */
final class JsonSchemaValidator
{
    /**
     * @param array<string, mixed> $schema
     * @return array<string, string> dotted path => violated keyword (`type`, `enum`, `required`, `additionalProperties` ...)
     */
    public function validate(mixed $value, array $schema, string $path = ''): array
    {
        $errors = [];
        $type = $schema['type'] ?? null;

        if (is_string($type) && ! $this->isType($value, $type)) {
            return [$path => 'type'];
        }

        if (isset($schema['enum']) && ! in_array($value, (array) $schema['enum'], true)) {
            $errors[$path] = 'enum';
        }

        if (is_int($value) || is_float($value)) {
            if (isset($schema['minimum']) && $value < $schema['minimum']) {
                $errors[$path] = 'minimum';
            }

            if (isset($schema['maximum']) && $value > $schema['maximum']) {
                $errors[$path] = 'maximum';
            }
        }

        if (is_string($value) && isset($schema['maxLength']) && mb_strlen($value) > (int) $schema['maxLength']) {
            $errors[$path] = 'maxLength';
        }

        if (is_array($value) && $type === 'array') {
            return $errors + $this->array($value, $schema, $path);
        }

        if (is_array($value) && $type === 'object') {
            return $errors + $this->object($value, $schema, $path);
        }

        return $errors;
    }

    /**
     * @param array<mixed> $value
     * @param array<string, mixed> $schema
     * @return array<string, string>
     */
    private function array(array $value, array $schema, string $path): array
    {
        $errors = [];

        if (isset($schema['maxItems']) && count($value) > (int) $schema['maxItems']) {
            $errors[$path] = 'maxItems';
        }

        if (($schema['uniqueItems'] ?? false) === true && count(array_unique(array_map(static fn (mixed $item): string => serialize($item), $value))) !== count($value)) {
            $errors[$path] = 'uniqueItems';
        }

        foreach ($value as $index => $item) {
            if (isset($schema['items']) && is_array($schema['items'])) {
                $errors += $this->validate($item, $schema['items'], ltrim($path.'.'.$index, '.'));
            }
        }

        return $errors;
    }

    /**
     * @param array<mixed> $value
     * @param array<string, mixed> $schema
     * @return array<string, string>
     */
    private function object(array $value, array $schema, string $path): array
    {
        $errors = [];
        $properties = (array) ($schema['properties'] ?? []);

        foreach ((array) ($schema['required'] ?? []) as $key) {
            if (! array_key_exists($key, $value) || $value[$key] === null) {
                $errors[ltrim($path.'.'.$key, '.')] = 'required';
            }
        }

        foreach ($value as $key => $item) {
            $child = ltrim($path.'.'.$key, '.');

            if (! isset($properties[$key])) {
                if (($schema['additionalProperties'] ?? true) === false) {
                    $errors[$child] = 'additionalProperties';
                }

                continue;
            }

            if ($item !== null) {
                $errors += $this->validate($item, (array) $properties[$key], $child);
            }
        }

        return $errors;
    }

    private function isType(mixed $value, string $type): bool
    {
        return match ($type) {
            'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
            'array' => is_array($value) && array_is_list($value),
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            default => true,
        };
    }
}
