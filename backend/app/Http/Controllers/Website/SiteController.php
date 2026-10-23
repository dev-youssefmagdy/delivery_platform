<?php

namespace App\Http\Controllers\Website;

use App\Enums\Common\Locale;
use App\Http\Controllers\Controller;
use App\Services\Content\ContentReadService;
use App\Support\Website\MetaBuilder;
use App\Support\Website\PageMeta;
use App\Support\Website\SitePaths;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Base of the public website controllers: thin actions that read published content through ContentReadService only
 * (never the models) and hand localized data plus PageMeta to a Blade view. Business rules live in services.
 */
abstract class SiteController extends Controller
{
    public function __construct(
        protected readonly ContentReadService $content,
        protected readonly MetaBuilder $meta,
        protected readonly SitePaths $paths,
    ) {}

    protected function locale(): string
    {
        return Locale::tryFromValue(app()->getLocale())->value ?? Locale::default()->value;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function page(string $view, array $data, PageMeta $meta): View
    {
        return view($view, $data + ['meta' => $meta]);
    }

    protected function notFound(): Response
    {
        return response()->view('website.pages.not-found', ['meta' => $this->meta->private((string) __('site.not_found.title'))], 404);
    }

    /**
     * The Organization structured data (name, url, support contact, social profiles).
     *
     * @return array<string, mixed>
     */
    protected function organization(): array
    {
        $contact = array_filter([
            '@type' => 'ContactPoint',
            'contactType' => 'customer support',
            'email' => config('website.contact.support_email'),
            'telephone' => config('website.contact.support_phone'),
            'availableLanguage' => ['ar', 'en'],
        ]);

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => (string) config('website.brand.name'),
            'url' => $this->paths->url('home'),
            'contactPoint' => count($contact) > 3 ? $contact : null,
            'sameAs' => array_values(array_filter((array) config('website.social'))) ?: null,
        ]);
    }
}
