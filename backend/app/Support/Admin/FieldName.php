<?php

namespace App\Support\Admin;

use Illuminate\Support\Str;

/**
 * Field name helpers shared by the admin form components: bracket names (`items[0][name]`, `tags[]`) map to
 * Laravel's dot error keys (`items.0.name`, `tags`) and to stable DOM ids.
 */
final class FieldName
{
    public static function key(string $name): string
    {
        $key = preg_replace('/\[\]$/', '', $name) ?? $name;

        return trim(str_replace(['][', '[', ']'], ['.', '.', ''], $key), '.');
    }

    public static function id(string $name, ?string $prefix = 'f'): string
    {
        return ($prefix !== null ? $prefix.'-' : '').Str::of(self::key($name))->replace(['.', '_'], '-')->slug()->toString();
    }

    /**
     * Current value of a field from old input or the given default (non-AJAX fallbacks, edit forms).
     */
    public static function value(string $name, mixed $default = null): mixed
    {
        return old(self::key($name), $default);
    }
}
