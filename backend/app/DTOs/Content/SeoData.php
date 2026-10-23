<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\NormalizesInput;

/**
 * SEO fields of a page or article: title, description, Open Graph, canonical URL and noindex.
 */
final readonly class SeoData
{
    use NormalizesInput;

    /**
     * @param array<string, string|null> $title
     * @param array<string, string|null> $description
     * @param array<string, string|null> $ogTitle
     * @param array<string, string|null> $ogDescription
     */
    public function __construct(
        public array $title = [],
        public array $description = [],
        public array $ogTitle = [],
        public array $ogDescription = [],
        public ?int $ogImageId = null,
        public ?string $canonicalUrl = null,
        public bool $noindex = false,
    ) {}

    /**
     * @param array<string, mixed> $data validated request data
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            title: self::translations($data['seo_title'] ?? []),
            description: self::translations($data['seo_description'] ?? []),
            ogTitle: self::translations($data['og_title'] ?? []),
            ogDescription: self::translations($data['og_description'] ?? []),
            ogImageId: self::id($data['og_image_id'] ?? null),
            canonicalUrl: self::text($data['canonical_url'] ?? null),
            noindex: (bool) ($data['noindex'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'seo_title' => self::filled($this->title),
            'seo_description' => self::filled($this->description),
            'og_title' => self::filled($this->ogTitle),
            'og_description' => self::filled($this->ogDescription),
            'og_image_id' => $this->ogImageId,
            'canonical_url' => $this->canonicalUrl,
            'noindex' => $this->noindex,
        ];
    }
}
