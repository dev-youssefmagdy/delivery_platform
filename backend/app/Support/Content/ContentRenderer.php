<?php

namespace App\Support\Content;

use App\Enums\Content\BodyFormat;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Turns editor input (Markdown or restricted HTML) into the sanitized HTML that is stored and served.
 * Markdown first drops raw HTML and unsafe links, then every result goes through the allowlist sanitizer.
 */
final class ContentRenderer
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    public function render(?string $source, BodyFormat $format): string
    {
        $source = trim((string) $source);

        if ($source === '') {
            return '';
        }

        if ($format === BodyFormat::Markdown) {
            $converter = new GithubFlavoredMarkdownConverter(['html_input' => 'strip', 'allow_unsafe_links' => false, 'max_nesting_level' => 20]);
            $source = $converter->convert($source)->getContent();
        }

        return $this->sanitizer->sanitize($source);
    }

    /**
     * @param array<string, string|null> $sources locale => source
     * @return array<string, string>
     */
    public function renderTranslations(array $sources, BodyFormat $format): array
    {
        $rendered = [];

        foreach ($sources as $locale => $source) {
            $html = $this->render($source, $format);

            if ($html !== '') {
                $rendered[$locale] = $html;
            }
        }

        return $rendered;
    }

    public function sanitizer(): HtmlSanitizer
    {
        return $this->sanitizer;
    }
}
