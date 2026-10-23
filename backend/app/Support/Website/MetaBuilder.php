<?php

namespace App\Support\Website;

use App\Enums\Common\Locale;
use Illuminate\Support\Str;

/**
 * Builds PageMeta for static pages and for CMS content (its `seo` block: title, description, Open Graph, canonical, noindex,
 * available locales). The title always ends with the brand; the canonical URL is the page's own localized URL unless the
 * editor set one (a validated https URL); hreflang lists only the languages the page really exists in, plus `x-default`.
 */
final class MetaBuilder
{
    public function __construct(private readonly SitePaths $paths) {}

    /**
     * @param array<string, string> $params
     * @param array<string, mixed> $seo the `seo` block of a CMS payload (empty for static pages)
     * @param array<string, mixed>|null $image CMS image payload (`url`, `alt`, `width`, `height`)
     * @param list<array<string, mixed>> $jsonLd
     */
    public function make(
        string $key,
        array $params,
        string $title,
        ?string $description = null,
        array $seo = [],
        ?array $image = null,
        string $ogType = 'website',
        array $jsonLd = [],
        bool $noindex = false,
    ): PageMeta {
        $available = isset($seo['available_locales']) && is_array($seo['available_locales']) ? array_values($seo['available_locales']) : null;
        $own = $this->paths->url($key, $params);
        $canonical = isset($seo['canonical_url']) && is_string($seo['canonical_url']) && $seo['canonical_url'] !== '' ? $seo['canonical_url'] : $own;
        $alternates = $this->paths->alternates($key, $params, $available);

        $seoTitle = isset($seo['title']) && is_string($seo['title']) && $seo['title'] !== '' ? $seo['title'] : $title;
        $seoDescription = $seo['description'] ?? $description;

        return new PageMeta(
            title: $this->withBrand($seoTitle, $key === 'home'),
            description: is_string($seoDescription) && trim($seoDescription) !== '' ? Str::limit(trim(preg_replace('/\s+/u', ' ', $seoDescription) ?? ''), 300, '') : null,
            canonical: $canonical,
            alternates: $alternates,
            noindex: $noindex || (bool) ($seo['noindex'] ?? false),
            ogType: $ogType,
            image: $this->image($seo['og_image'] ?? $image),
            jsonLd: $jsonLd,
        );
    }

    /**
     * A page search engines must not index (private share links, receipts, thank-you pages).
     */
    public function private(string $title): PageMeta
    {
        return new PageMeta(title: $this->withBrand($title), description: null, canonical: null, noindex: true);
    }

    public function withBrand(string $title, bool $home = false): string
    {
        $brand = (string) config('website.brand.name');

        if ($home) {
            return $brand.' | '.$title;
        }

        return $title.' | '.$brand;
    }

    /**
     * @param array<string, mixed>|null $image
     * @return array{url: string, alt: ?string, width: ?int, height: ?int}|null
     */
    private function image(?array $image): ?array
    {
        if ($image === null || ! is_string($image['url'] ?? null)) {
            return null;
        }

        $url = $image['url'];

        return [
            'url' => Str::startsWith($url, ['http://', 'https://']) ? $url : rtrim(url('/'), '/').'/'.ltrim($url, '/'),
            'alt' => is_string($image['alt'] ?? null) ? $image['alt'] : null,
            'width' => isset($image['width']) ? (int) $image['width'] : null,
            'height' => isset($image['height']) ? (int) $image['height'] : null,
        ];
    }

    public function locale(): Locale
    {
        return Locale::tryFromValue(app()->getLocale()) ?? Locale::default();
    }
}
