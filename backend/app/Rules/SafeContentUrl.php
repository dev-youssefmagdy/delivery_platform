<?php

namespace App\Rules;

use App\Support\Content\HtmlSanitizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A link editors may store in the CMS: https (or http) URLs and, when allowed, site-relative paths, mailto: and tel:.
 * javascript:, data: and protocol-relative URLs are always refused.
 */
final class SafeContentUrl implements ValidationRule
{
    public function __construct(
        private readonly bool $allowRelative = true,
        private readonly bool $allowContact = false,
        private readonly bool $httpsOnly = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.url')->translate();

            return;
        }

        $url = trim($value);
        $sanitizer = new HtmlSanitizer;
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $relative = str_starts_with($url, '/') && ! str_starts_with($url, '//');
        $contact = is_string($scheme) && in_array(strtolower($scheme), ['mailto', 'tel'], true);

        $valid = $sanitizer->safeUrl($url)
            && ($relative ? $this->allowRelative : ($contact ? $this->allowContact : $this->absoluteAllowed($scheme, $url)));

        if (! $valid) {
            $fail('validation.url')->translate();
        }
    }

    private function absoluteAllowed(mixed $scheme, string $url): bool
    {
        if (! is_string($scheme)) {
            return false;
        }

        $scheme = strtolower($scheme);

        return ($scheme === 'https' || (! $this->httpsOnly && $scheme === 'http')) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
