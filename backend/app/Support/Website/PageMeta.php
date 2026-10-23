<?php

namespace App\Support\Website;

/**
 * Everything the layout needs in <head>: localized title and description, canonical URL, hreflang alternates, robots,
 * Open Graph and structured data. Built by MetaBuilder; the layout only prints it (values are escaped there).
 */
final readonly class PageMeta
{
    /**
     * @param array<string, string> $alternates locale => absolute URL (hreflang)
     * @param array{url: string, alt: ?string, width: ?int, height: ?int}|null $image Open Graph image
     * @param list<array<string, mixed>> $jsonLd structured data documents
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $canonical,
        public array $alternates = [],
        public bool $noindex = false,
        public string $ogType = 'website',
        public ?array $image = null,
        public array $jsonLd = [],
    ) {}

    public function robots(): string
    {
        return $this->noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large';
    }
}
