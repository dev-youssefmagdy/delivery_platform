<?php

namespace App\Support\Content;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allowlist HTML sanitizer for CMS rich text (SRS 16.13). The input is parsed into a DOM and rebuilt: only the
 * listed tags and attributes survive, URLs are limited to safe schemes, scripting/embedding elements are removed
 * with their content, unknown elements are unwrapped (text kept), comments are dropped. Nothing is passed through
 * as raw HTML, and the output is serialised by the DOM so quoting is always correct.
 */
final class HtmlSanitizer
{
    /** @var array<string, list<string>> tag => allowed attributes */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [], 'code' => [], 'pre' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    /** Removed together with everything inside them. */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'svg', 'math', 'form', 'input', 'button',
        'textarea', 'select', 'option', 'link', 'meta', 'base', 'noscript', 'template', 'title', 'head', 'audio', 'video',
        'source', 'canvas', 'xml', 'plaintext', 'xmp', 'listing', 'noembed', 'noframes',
    ];

    private const URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="utf-8" ?><body><div id="cms-root">'.$html.'</div></body>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('cms-root');

        if (! $root instanceof DOMElement) {
            return e(strip_tags($html));
        }

        $this->clean($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * Text only (meta descriptions, search): tags removed, entities decoded, whitespace collapsed.
     */
    public function plainText(?string $html, int $limit = 0): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->sanitize($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $limit > 0 ? mb_strimwidth($text, 0, $limit, '…') : $text;
    }

    private function clean(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                // Comments, processing instructions, CDATA, entity references.
                $parent->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);

                continue;
            }

            $this->clean($child);

            if (! array_key_exists($tag, self::ALLOWED)) {
                // Unknown element: keep its (already cleaned) children in its place.
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            $this->cleanAttributes($child, $tag);

            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $parent->removeChild($child);
            }
        }
    }

    private function cleanAttributes(DOMElement $element, string $tag): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);

            if (! in_array($name, self::ALLOWED[$tag], true)) {
                $element->removeAttribute($attribute->nodeName);

                continue;
            }

            $value = $attribute->nodeValue ?? '';

            if (($name === 'href' || $name === 'src') && ! $this->safeUrl($value, $name === 'src')) {
                $element->removeAttribute($attribute->nodeName);
            } elseif (in_array($name, ['colspan', 'rowspan', 'width', 'height'], true) && ! ctype_digit($value)) {
                $element->removeAttribute($attribute->nodeName);
            } elseif ($name === 'target' && $value !== '_blank') {
                $element->removeAttribute('target');
            }
        }

        if ($tag === 'a') {
            if ($element->getAttribute('target') === '_blank') {
                $element->setAttribute('rel', 'noopener noreferrer');
            } else {
                $element->removeAttribute('rel');
            }
        }

        if ($tag === 'img') {
            $element->setAttribute('loading', 'lazy');
        }
    }

    public function safeUrl(string $url, bool $image = false): bool
    {
        $url = preg_replace('/[\x00-\x20\x7F]+/u', '', $url) ?? '';

        if ($url === '' || preg_match('/[\\\\<>"\']/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '#')) {
            return ! $image;
        }

        if (str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! is_string($scheme)) {
            // Relative path such as "page" (a colon would have produced a scheme above).
            return ! str_contains($url, ':');
        }

        $schemes = $image ? ['http', 'https'] : self::URL_SCHEMES;

        return in_array(strtolower($scheme), $schemes, true);
    }
}
