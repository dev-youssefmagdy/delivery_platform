<?php

namespace App\Services\Notification;

/**
 * Fills `{variable}` placeholders. Values are plain text (push / SMS) or escaped by the email view; a placeholder with
 * no value renders empty, never as the raw `{name}`.
 */
final class TemplateRenderer
{
    private const PATTERN = '/\{([a-z_]{1,32})\}/';

    /**
     * @param array<string, scalar|null> $variables
     */
    public function render(string $template, array $variables): string
    {
        return trim((string) preg_replace_callback(
            self::PATTERN,
            static fn (array $match): string => isset($variables[$match[1]]) ? (string) $variables[$match[1]] : '',
            $template,
        ));
    }

    /**
     * @return list<string> the placeholders a text uses
     */
    public function placeholders(string $template): array
    {
        preg_match_all(self::PATTERN, $template, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param list<string> $allowed
     * @return list<string> placeholders in the text that the event does not provide
     */
    public function unknown(string $template, array $allowed): array
    {
        return array_values(array_diff($this->placeholders($template), $allowed));
    }
}
