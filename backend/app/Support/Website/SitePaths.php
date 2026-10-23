<?php

namespace App\Support\Website;

use App\Enums\Common\Locale;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * URL map of the public website. Arabic (default) is unprefixed, English lives under /en. Every page has a localized path
 * (lang/{locale}/site.php `paths`), so Arabic pages have Arabic slugs and English pages English ones.
 *
 * CMS menus and links use the stable ENGLISH paths (`/how-it-works`, `/legal/terms`, ...): `resolve()` maps them to the
 * localized URL of the visitor's language, so a menu authored once works in both languages. Unknown site paths are kept
 * (prefixed with the locale); `https:`, `mailto:` and `tel:` links are external.
 */
final class SitePaths
{
    /**
     * Page keys. Each has a `paths.{key}` entry per locale; `{slug}` and `{type}` are parameters.
     *
     * @var list<string>
     */
    public const KEYS = [
        'home', 'customer-app', 'apps', 'become-provider', 'how-it-works', 'safety', 'about', 'contact',
        'services', 'service', 'cities', 'city', 'help', 'help-article', 'blog', 'blog-article', 'legal-index', 'legal',
    ];

    /**
     * The path template of a key in a locale, without prefix and without leading slash.
     */
    public function template(string $key, Locale|string $locale): string
    {
        $locale = $locale instanceof Locale ? $locale->value : $locale;

        return trim((string) Lang::get("site.paths.{$key}", [], $locale), '/');
    }

    /**
     * Route URI (with the locale prefix) used when registering routes.
     */
    public function routeUri(string $key, Locale $locale): string
    {
        $path = $this->template($key, $locale);
        $prefix = $locale === Locale::default() ? '' : $locale->value;

        $uri = trim($prefix.'/'.$path, '/');

        return $uri === '' ? '/' : $uri;
    }

    /**
     * @param array<string, string> $params
     */
    public function path(string $key, array $params = [], Locale|string|null $locale = null): string
    {
        $locale = $this->locale($locale);
        $path = $this->template($key, $locale);

        foreach ($params as $name => $value) {
            $path = str_replace('{'.$name.'}', $value, $path);
        }

        // Percent-encode every segment (Arabic slugs become %D8%.. in links, canonicals, hreflang and the sitemap).
        $path = implode('/', array_map('rawurlencode', explode('/', $path)));

        $prefix = $locale === Locale::default() ? '' : '/'.$locale->value;

        $full = rtrim($prefix.'/'.$path, '/');

        return $full === '' ? '/' : $full;
    }

    /**
     * @param array<string, string> $params
     */
    public function url(string $key, array $params = [], Locale|string|null $locale = null): string
    {
        return rtrim(url('/'), '/').$this->path($key, $params, $locale);
    }

    /**
     * The same page in the other language(s): locale => absolute URL.
     *
     * @param array<string, string> $params
     * @param list<string>|null $only restrict to these locales (a CMS item that has no English version has no English alternate)
     * @return array<string, string>
     */
    public function alternates(string $key, array $params = [], ?array $only = null): array
    {
        $urls = [];

        foreach (Locale::cases() as $locale) {
            if ($only === null || in_array($locale->value, $only, true)) {
                $urls[$locale->value] = $this->url($key, $params, $locale);
            }
        }

        return $urls;
    }

    /**
     * A CMS / menu / CTA link as the website should render it.
     *
     * @return array{href: string, external: bool}
     */
    public function resolve(string $link, Locale|string|null $locale = null): array
    {
        $locale = $this->locale($locale);
        $link = trim($link);

        if (preg_match('#^(https?://|mailto:|tel:)#i', $link) === 1) {
            return ['href' => $link, 'external' => preg_match('#^https?://#i', $link) === 1 && ! $this->isOwnHost($link)];
        }

        if ($link === '' || $link[0] !== '/') {
            return ['href' => $this->url('home', [], $locale), 'external' => false];
        }

        $path = trim((string) parse_url($link, PHP_URL_PATH), '/');
        $query = parse_url($link, PHP_URL_QUERY);
        $fragment = parse_url($link, PHP_URL_FRAGMENT);
        $suffix = ($query !== null && $query !== false && $query !== '' ? '?'.$query : '').($fragment !== null && $fragment !== false && $fragment !== '' ? '#'.$fragment : '');

        foreach (self::KEYS as $key) {
            $params = $this->match($this->template($key, Locale::English), $path);

            if ($params !== null) {
                return ['href' => $this->path($key, $params, $locale).$suffix, 'external' => false];
            }
        }

        // Not a known page: keep the path, prefixed with the locale (the website answers 404 if it does not exist).
        $prefix = $locale === Locale::default() ? '' : '/'.$locale->value;

        return ['href' => $prefix.'/'.$path.$suffix, 'external' => false];
    }

    /**
     * @return array<string, string>|null parameters when `$path` fits the template
     */
    private function match(string $template, string $path): ?array
    {
        $regex = '#^'.preg_replace('#\\\\\{(\w+)\\\\\}#', '(?P<$1>[^/]+)', preg_quote($template, '#')).'$#u';

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        return array_map(static fn (string $value): string => rawurldecode($value), array_filter($matches, static fn (mixed $key): bool => is_string($key), ARRAY_FILTER_USE_KEY));
    }

    private function isOwnHost(string $url): bool
    {
        return Str::startsWith($url, rtrim(url('/'), '/').'/') || $url === rtrim(url('/'), '/');
    }

    private function locale(Locale|string|null $locale): Locale
    {
        if ($locale instanceof Locale) {
            return $locale;
        }

        return Locale::tryFromValue($locale ?? app()->getLocale()) ?? Locale::default();
    }
}
