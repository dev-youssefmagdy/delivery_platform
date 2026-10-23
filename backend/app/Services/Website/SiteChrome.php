<?php

namespace App\Services\Website;

use App\Enums\Common\Locale;
use App\Enums\Content\BannerPlacement;
use App\Enums\Content\MenuLocation;
use App\Services\Content\ContentReadService;
use App\Support\Website\SitePaths;

/**
 * Header, footer, announcement bar, store links and contact details of the layout. Menus come from the CMS
 * (ContentReadService::menus, English-path links resolved to the visitor's localized URLs); when no menu is published the
 * built-in navigation of lang/{locale}/site.php is used so the site is never without navigation.
 */
final class SiteChrome
{
    public function __construct(private readonly ContentReadService $content, private readonly SitePaths $paths) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $locale = null): array
    {
        $locale = Locale::tryFromValue($locale ?? app()->getLocale())?->value ?? Locale::default()->value;
        $menus = $this->content->menus($locale);
        $banners = $this->content->banners([BannerPlacement::WebTopBar], null, $locale);

        return [
            'header' => $this->items($menus[MenuLocation::Header->value] ?? null, $this->defaultHeader($locale), $locale),
            'footer_explore' => $this->items($menus[MenuLocation::FooterExplore->value] ?? null, $this->defaultHeader($locale), $locale),
            'footer_legal' => $this->items($menus[MenuLocation::FooterLegal->value] ?? null, $this->defaultLegal($locale), $locale),
            'app_links' => $this->content->appLinks(),
            'banner' => $banners[0] ?? null,
            'contact' => ['email' => config('website.contact.support_email'), 'phone' => config('website.contact.support_phone')],
            'social' => (array) config('website.social'),
        ];
    }

    /**
     * @param array<string, mixed>|null $menu
     * @param list<array{label: string, href: string, external: bool, new_tab: bool}> $fallback
     * @return list<array{label: string, href: string, external: bool, new_tab: bool}>
     */
    private function items(?array $menu, array $fallback, string $locale): array
    {
        if ($menu === null || $menu['items'] === []) {
            return $fallback;
        }

        return array_values(array_map(function (array $item) use ($locale): array {
            $target = $this->paths->resolve((string) $item['url'], $locale);

            return ['label' => (string) $item['label'], 'href' => $target['href'], 'external' => $target['external'], 'new_tab' => (bool) $item['new_tab'] || $target['external']];
        }, $menu['items']));
    }

    /**
     * @return list<array{label: string, href: string, external: bool, new_tab: bool}>
     */
    private function defaultHeader(string $locale): array
    {
        $items = [];

        foreach (['how-it-works', 'services', 'cities', 'safety', 'help', 'become-provider', 'about', 'contact'] as $key) {
            $items[] = ['label' => (string) __('site.nav.'.$key, [], $locale), 'href' => $this->paths->path($key, [], $locale), 'external' => false, 'new_tab' => false];
        }

        return $items;
    }

    /**
     * @return list<array{label: string, href: string, external: bool, new_tab: bool}>
     */
    private function defaultLegal(string $locale): array
    {
        $items = [];

        foreach ($this->content->legalPages($locale) as $page) {
            $items[] = ['label' => (string) $page['title'], 'href' => $this->paths->path('legal', ['type' => (string) $page['type']], $locale), 'external' => false, 'new_tab' => false];
        }

        return $items;
    }
}
