<?php

namespace App\Services\Website;

use App\Enums\Common\Locale;
use App\Enums\Content\ArticleType;
use App\Enums\Content\PageKind;
use App\Services\Content\ContentReadService;
use App\Support\Website\SitePaths;
use Illuminate\Support\Facades\Cache;

/**
 * The dynamic sitemap: every public page in every language it exists in, from the published CMS content (read service only), the
 * legal documents and the fixed pages. Each URL lists its language alternates (hreflang) and `x-default` (Arabic). Pages an
 * editor marked noindex are left out. Cached for `website.sitemap_ttl_seconds`.
 */
final class SitemapService
{
    /** Fixed pages that always exist (built-in content or a form). */
    private const FIXED = ['home', 'customer-app', 'apps', 'become-provider', 'services', 'cities', 'help', 'blog', 'legal-index', 'contact'];

    /** Pages that exist only while the operator has published the CMS page with this slug. */
    private const CMS_BACKED = ['how-it-works' => 'how-it-works', 'safety' => 'safety', 'about' => 'about'];

    public function __construct(private readonly ContentReadService $content, private readonly SitePaths $paths) {}

    public function xml(): string
    {
        return Cache::remember('website:sitemap', (int) config('website.sitemap_ttl_seconds', 300), fn (): string => $this->render($this->entries()));
    }

    /**
     * @return list<array{locs: array<string, string>, lastmod: ?string}>
     */
    public function entries(): array
    {
        $entries = [];

        foreach (self::FIXED as $key) {
            $entries[] = $this->entry($key, [], null, Locale::cases());
        }

        foreach (self::CMS_BACKED as $key => $slug) {
            $entry = $this->cmsEntry($key, [], $slug);

            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        foreach ([PageKind::Service->value => 'service', PageKind::City->value => 'city'] as $kind => $key) {
            foreach ($this->slugs(PageKind::from($kind)) as $slug) {
                $entry = $this->cmsEntry($key, ['slug' => $slug], $slug);

                if ($entry !== null) {
                    $entries[] = $entry;
                }
            }
        }

        foreach ([ArticleType::Help->value => 'help-article', ArticleType::Blog->value => 'blog-article'] as $type => $key) {
            foreach ($this->articleSlugs(ArticleType::from($type)) as $slug) {
                $entry = $this->articleEntry($key, $slug);

                if ($entry !== null) {
                    $entries[] = $entry;
                }
            }
        }

        foreach ($this->content->legalPages(Locale::default()->value) as $document) {
            $entries[] = $this->entry('legal', ['type' => (string) $document['type']], $document['published_at'] ?? null, Locale::cases());
        }

        return $entries;
    }

    /**
     * @param array<string, string> $params
     * @param list<Locale> $locales
     * @return array{locs: array<string, string>, lastmod: ?string}
     */
    private function entry(string $key, array $params, ?string $lastmod, array $locales): array
    {
        $locs = [];

        foreach ($locales as $locale) {
            $locs[$locale->value] = $this->paths->url($key, $params, $locale);
        }

        return ['locs' => $locs, 'lastmod' => $lastmod];
    }

    /**
     * @param array<string, string> $params
     * @return array{locs: array<string, string>, lastmod: ?string}|null
     */
    private function cmsEntry(string $key, array $params, string $slug): ?array
    {
        $locales = [];
        $lastmod = null;

        foreach (Locale::cases() as $locale) {
            $page = $this->content->page($slug, $locale->value);

            if ($page === null || ($page['seo']['noindex'] ?? false)) {
                continue;
            }

            $locales[] = $locale;
            $lastmod = max((string) $lastmod, (string) ($page['updated_at'] ?? '')) ?: null;
        }

        return $locales === [] ? null : $this->entry($key, $params, $lastmod, $locales);
    }

    /**
     * @return array{locs: array<string, string>, lastmod: ?string}|null
     */
    private function articleEntry(string $key, string $slug): ?array
    {
        $locales = [];
        $lastmod = null;

        foreach (Locale::cases() as $locale) {
            $article = $this->content->article($slug, $locale->value);

            if ($article === null || ($article['seo']['noindex'] ?? false)) {
                continue;
            }

            $locales[] = $locale;
            $lastmod = max((string) $lastmod, (string) ($article['updated_at'] ?? '')) ?: null;
        }

        return $locales === [] ? null : $this->entry($key, ['slug' => $slug], $lastmod, $locales);
    }

    /**
     * Slugs of published pages of a kind (Arabic is always served, so its list is complete).
     *
     * @return list<string>
     */
    private function slugs(PageKind $kind): array
    {
        return array_values(array_map(static fn (array $page): string => (string) $page['slug'], $this->content->pages($kind, Locale::default()->value)));
    }

    /**
     * @return list<string>
     */
    private function articleSlugs(ArticleType $type): array
    {
        return array_values(array_map(static fn (array $article): string => (string) $article['slug'], $this->content->articles($type, null, 1000, Locale::default()->value)));
    }

    /**
     * @param list<array{locs: array<string, string>, lastmod: ?string}> $entries
     */
    private function render(array $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($entries as $entry) {
            $alternates = '';

            foreach ($entry['locs'] as $locale => $url) {
                $alternates .= '    <xhtml:link rel="alternate" hreflang="'.$this->e($locale).'" href="'.$this->e($url).'"/>'."\n";
            }

            if (isset($entry['locs'][Locale::default()->value])) {
                $alternates .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'.$this->e($entry['locs'][Locale::default()->value]).'"/>'."\n";
            }

            foreach ($entry['locs'] as $url) {
                $xml .= "  <url>\n    <loc>".$this->e($url)."</loc>\n";

                if ($entry['lastmod'] !== null) {
                    $xml .= '    <lastmod>'.$this->e(substr($entry['lastmod'], 0, 10))."</lastmod>\n";
                }

                $xml .= $alternates."  </url>\n";
            }
        }

        return $xml.'</urlset>'."\n";
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
