<?php

namespace App\Services\Content;

use App\Enums\Content\BlockType;
use App\Enums\Content\BodyFormat;
use App\Enums\Content\ContentType;
use App\Support\Content\ContentRenderer;

/**
 * The single place where editor input becomes servable content: rich text sources are rendered to sanitized HTML
 * (`body_html`, `answer_html`, and `body_html` inside blocks). Used by create, update and revision restore, so no
 * path can store HTML that did not go through the sanitizer.
 */
final class ContentPayloadNormalizer
{
    public function __construct(private readonly ContentRenderer $renderer) {}

    /**
     * @param array<string, mixed> $attributes source fields of the entity
     * @return array<string, mixed> the same attributes plus the derived sanitized fields
     */
    public function derive(ContentType $type, array $attributes): array
    {
        return match ($type) {
            ContentType::Page => $this->page($attributes),
            ContentType::Article => $this->richBody($attributes),
            ContentType::Faq => $this->faq($attributes),
            default => $attributes,
        };
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function richBody(array $attributes): array
    {
        $format = $this->format($attributes['body_format'] ?? null);
        $attributes['body_html'] = $this->renderer->renderTranslations((array) ($attributes['body'] ?? []), $format);

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function page(array $attributes): array
    {
        $attributes = $this->richBody($attributes);
        $blocks = [];

        foreach ((array) ($attributes['blocks'] ?? []) as $block) {
            if (! is_array($block) || BlockType::tryFrom((string) ($block['type'] ?? '')) === null) {
                continue;
            }

            $block['body_html'] = $this->renderer->renderTranslations((array) ($block['body'] ?? []), BodyFormat::Markdown);
            $blocks[] = $block;
        }

        $attributes['blocks'] = $blocks;

        return $attributes;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function faq(array $attributes): array
    {
        $attributes['answer_html'] = $this->renderer->renderTranslations((array) ($attributes['answer'] ?? []), BodyFormat::Markdown);

        return $attributes;
    }

    private function format(mixed $value): BodyFormat
    {
        return $value instanceof BodyFormat ? $value : (BodyFormat::tryFrom((string) $value) ?? BodyFormat::Markdown);
    }
}
