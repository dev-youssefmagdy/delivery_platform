<?php

namespace App\Http\Controllers\Website;

use App\Enums\Content\BannerPlacement;
use Illuminate\Contracts\View\View;

/**
 * Home (/): the CMS `home` page (blocks); when none is published a built-in explanation of the propose-a-fare model keeps the
 * site useful. Prices are never shown here: fares are always agreed in the app.
 */
final class HomeController extends SiteController
{
    public function show(): View
    {
        $page = $this->content->page('home', $this->locale());
        $seo = $page['seo'] ?? [];
        $title = (string) ($page['title'] ?? __('site.home.title'));
        $description = ($page['summary'] ?? null) ?: (string) __('site.home.description');

        $meta = $this->meta->make(
            'home',
            [],
            (string) ($seo['title'] ?? __('site.home.meta_title')),
            $description,
            $seo,
            $page['hero_image'] ?? null,
            jsonLd: [
                $this->organization(),
                ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => (string) config('website.brand.name'), 'url' => $this->paths->url('home'), 'inLanguage' => $this->locale()],
            ],
        );

        return $this->page('website.pages.home', [
            'cmsPage' => $page,
            'title' => $title,
            'banners' => $this->content->banners([BannerPlacement::WebHome], null, $this->locale()),
            'appLinks' => $this->content->appLinks(),
        ], $meta);
    }
}
