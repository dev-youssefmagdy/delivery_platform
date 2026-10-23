<?php

namespace App\Http\Controllers\Website;

use App\Enums\Content\BannerPlacement;
use App\Enums\Content\PageKind;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * CMS-driven pages: how it works, safety, about, the customer app page, the services index and service pages, the cities index
 * and city pages. Content is read through ContentReadService only; a page that is not published (or has no version in this
 * language and does not fall back) is a 404, never an invented page.
 */
final class ContentPageController extends SiteController
{
    public function howItWorks(): View|Response
    {
        return $this->generic('how-it-works', 'how-it-works');
    }

    public function safety(): View|Response
    {
        return $this->generic('safety', 'safety');
    }

    public function about(): View|Response
    {
        return $this->generic('about', 'about', [$this->organization()]);
    }

    /**
     * The customer app page: a CMS page with slug `customer-app` when the operator wrote one, else the built-in description.
     */
    public function customerApp(): View
    {
        $page = $this->content->page('customer-app', $this->locale());
        $seo = $page['seo'] ?? [];
        $meta = $this->meta->make('customer-app', [], (string) ($page['title'] ?? __('site.customer_app.title')), ($page['summary'] ?? null) ?: (string) __('site.customer_app.description'), $seo, $page['hero_image'] ?? null);

        return $this->page('website.pages.customer-app', ['cmsPage' => $page, 'appLinks' => $this->content->appLinks()], $meta);
    }

    public function services(): View
    {
        $services = $this->content->pages(PageKind::Service, $this->locale());
        $meta = $this->meta->make('services', [], (string) __('site.services.title'), (string) __('site.services.description'));

        return $this->page('website.pages.services', [
            'services' => $services,
            'banners' => $this->content->banners([BannerPlacement::WebServices], null, $this->locale()),
        ], $meta);
    }

    public function service(string $slug): View|Response
    {
        $page = $this->content->page($slug, $this->locale());

        if ($page === null || $page['kind'] !== PageKind::Service->value) {
            return $this->notFound();
        }

        $faqs = $page['service_category'] === null ? ['faqs' => []] : $this->content->faqs(null, $page['service_category']['code'], $this->locale());
        $meta = $this->meta->make('service', ['slug' => $slug], $page['title'], $page['summary'], $page['seo'], $page['hero_image'], jsonLd: [
            ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $page['title'], 'description' => $page['summary'], 'provider' => ['@type' => 'Organization', 'name' => (string) config('website.brand.name')], 'url' => $this->paths->url('service', ['slug' => $slug])],
        ]);

        return $this->page('website.pages.service', ['cmsPage' => $page, 'faqs' => array_slice($faqs['faqs'], 0, 6), 'appLinks' => $this->content->appLinks()], $meta);
    }

    public function cities(): View
    {
        $meta = $this->meta->make('cities', [], (string) __('site.cities.title'), (string) __('site.cities.description'));

        return $this->page('website.pages.cities', ['cities' => $this->content->cities($this->locale())], $meta);
    }

    public function city(string $slug): View|Response
    {
        $page = $this->content->page($slug, $this->locale());

        if ($page === null || $page['kind'] !== PageKind::City->value) {
            return $this->notFound();
        }

        $meta = $this->meta->make('city', ['slug' => $slug], $page['title'], $page['summary'], $page['seo'], $page['hero_image']);

        return $this->page('website.pages.page', ['cmsPage' => $page, 'backKey' => 'cities', 'appLinks' => $this->content->appLinks()], $meta);
    }

    /**
     * @param list<array<string, mixed>> $jsonLd
     */
    private function generic(string $slug, string $key, array $jsonLd = []): View|Response
    {
        $page = $this->content->page($slug, $this->locale());

        if ($page === null) {
            return $this->notFound();
        }

        $meta = $this->meta->make($key, [], $page['title'], $page['summary'], $page['seo'], $page['hero_image'], jsonLd: $jsonLd);

        return $this->page('website.pages.page', ['cmsPage' => $page, 'appLinks' => $this->content->appLinks()], $meta);
    }
}
