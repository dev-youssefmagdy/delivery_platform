<?php

namespace App\Services\Content;

use App\Enums\Common\Locale;
use App\Enums\Content\ArticleType;
use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerPlacement;
use App\Enums\Content\BlockType;
use App\Enums\Content\PageKind;
use App\Enums\Policy\PolicyType;
use App\Models\City;
use App\Models\ContentAppLink;
use App\Models\ContentArticle;
use App\Models\ContentBanner;
use App\Models\ContentFaq;
use App\Models\ContentMedia;
use App\Models\ContentMenu;
use App\Models\ContentPage;
use App\Repositories\Contracts\Content\ContentReadRepositoryInterface;
use App\Services\Policy\PolicyReadService;
use App\Support\Content\HtmlSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

/**
 * The single read service for published content: the public website (P5.5) and the apps (/api/v1/content/*) both call
 * it, so they cannot disagree. Everything returned is already localized (requested language, Arabic fallback only
 * where the item allows it), contains only published content, and carries sanitized HTML produced at save time.
 * Results are cached per locale; any content change invalidates the cache (ContentCache).
 *
 * Legal pages keep the policy_versions mechanism: `policy()` / `legalPages()` expose it in the same shape. Policy text
 * is PLAIN TEXT (not HTML): the website must escape it and keep line breaks (e() + white-space: pre-line).
 */
final class ContentReadService
{
    public function __construct(
        private readonly ContentReadRepositoryInterface $content,
        private readonly ContentLocalizer $localizer,
        private readonly ContentCache $cache,
        private readonly PolicyReadService $policies,
        private readonly HtmlSanitizer $sanitizer,
    ) {}

    /**
     * @return array<string, mixed>|null null when there is no published page with that slug in the language
     */
    public function page(string $slug, ?string $locale = null): ?array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember("page:{$slug}:{$locale}", function () use ($slug, $locale): ?array {
            $page = $this->content->livePage($slug);

            return $page instanceof ContentPage ? $this->pagePayload($page, $locale, true) : null;
        });
    }

    /**
     * Published pages of a kind (service pages, city pages), without blocks and body.
     *
     * @return list<array<string, mixed>>
     */
    public function pages(PageKind $kind, ?string $locale = null): array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember("pages:{$kind->value}:{$locale}", fn (): array => $this->content->livePages($kind)
            ->map(fn (ContentPage $page): ?array => $this->pagePayload($page, $locale, false))
            ->filter()
            ->values()
            ->all());
    }

    /**
     * @param list<BannerPlacement> $placements
     * @return list<array<string, mixed>>
     */
    public function banners(array $placements = [], ?BannerAudience $audience = null, ?string $locale = null): array
    {
        $locale = $this->locale($locale);
        $key = 'banners:'.implode(',', array_map(static fn (BannerPlacement $placement): string => $placement->value, $placements)).':'.($audience->value ?? '*').":{$locale}";

        return $this->cache->remember($key, fn (): array => $this->content->liveBanners($placements, $audience, Carbon::now())
            ->map(fn (ContentBanner $banner): ?array => $this->bannerPayload($banner, $locale))
            ->filter()
            ->values()
            ->all());
    }

    /**
     * @return array{categories: list<array<string, mixed>>, faqs: list<array<string, mixed>>}
     */
    public function faqs(?string $categorySlug = null, ?string $serviceCode = null, ?string $locale = null): array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember("faqs:{$categorySlug}:{$serviceCode}:{$locale}", function () use ($categorySlug, $serviceCode, $locale): array {
            $faqs = $this->content->liveFaqs($categorySlug, $serviceCode)
                ->map(fn (ContentFaq $faq): ?array => $this->faqPayload($faq, $locale))
                ->filter()
                ->values()
                ->all();
            $used = array_flip(array_filter(array_column($faqs, 'category_slug')));
            $categories = $this->content->activeCategories(\App\Enums\Content\CategoryKind::Faq)
                ->filter(static fn (Model $category): bool => isset($used[$category->getAttribute('slug')]))
                ->map(fn (Model $category): array => [
                    'slug' => $category->getAttribute('slug'),
                    'name' => $this->localizer->text($category, 'name', $locale),
                    'description' => $this->localizer->text($category, 'description', $locale),
                ])
                ->values()
                ->all();

            return ['categories' => $categories, 'faqs' => $faqs];
        });
    }

    /**
     * Help-center / blog article list (no body).
     *
     * @return list<array<string, mixed>>
     */
    public function articles(?ArticleType $type = null, ?string $categorySlug = null, int $limit = 100, ?string $locale = null): array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember('articles:'.($type->value ?? '*').":{$categorySlug}:{$limit}:{$locale}", fn (): array => $this->content->liveArticles($type, $categorySlug, $limit)
            ->map(fn (ContentArticle $article): ?array => $this->articlePayload($article, $locale, false))
            ->filter()
            ->values()
            ->all());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function article(string $slug, ?string $locale = null): ?array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember("article:{$slug}:{$locale}", function () use ($slug, $locale): ?array {
            $article = $this->content->liveArticle($slug);

            return $article instanceof ContentArticle ? $this->articlePayload($article, $locale, true) : null;
        });
    }

    /**
     * Header / footer menus keyed by location.
     *
     * @return array<string, array<string, mixed>>
     */
    public function menus(?string $locale = null): array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember("menus:{$locale}", function () use ($locale): array {
            $menus = [];

            foreach ($this->content->liveMenus() as $menu) {
                $payload = $this->menuPayload($menu, $locale);

                if ($payload !== null) {
                    $menus[$menu->location->value] = $payload;
                }
            }

            return $menus;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function appLinks(): array
    {
        return $this->cache->remember('app-links', fn (): array => $this->content->activeAppLinks()
            ->map(static fn (ContentAppLink $link): array => ['app' => $link->app->value, 'platform' => $link->platform->value, 'url' => $link->url])
            ->values()
            ->all());
    }

    /**
     * Active cities with the slug of their published city page (null when there is none).
     *
     * @return list<array<string, mixed>>
     */
    public function cities(?string $locale = null): array
    {
        $locale = $this->locale($locale);

        return $this->cache->remember("cities:{$locale}", function () use ($locale): array {
            $pages = $this->content->livePages(PageKind::City)->keyBy('city_id');

            return $this->content->activeCities()
                ->map(function (City $city) use ($pages, $locale): array {
                    $page = $pages->get($city->id);

                    return [
                        'id' => $city->id,
                        'name' => (string) $city->getTranslation('name', $locale, true),
                        'page_slug' => $page instanceof ContentPage && $this->localizer->available($page, 'title', $locale) ? $page->slug : null,
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * Current published legal text (terms, privacy ...): the policy_versions mechanism, unchanged.
     *
     * @return array<string, mixed>
     *
     * @throws ModelNotFoundException when the type has no published version
     */
    public function policy(PolicyType $type, ?string $locale = null): array
    {
        $locale = $this->locale($locale);
        $policy = $this->policies->current($type);

        return [
            'type' => $type->value,
            'version' => $policy->version,
            'title' => (string) ($policy->getTranslation('title', $locale, true) ?: $policy->getTranslation('title', Locale::default()->value, false)),
            'content' => (string) ($policy->getTranslation('content', $locale, true) ?: $policy->getTranslation('content', Locale::default()->value, false)),
            'content_format' => 'plain_text',
            'published_at' => $policy->published_at?->toIso8601ZuluString(),
        ];
    }

    /**
     * Every legal document that currently has a published version (footer / legal index of the website).
     *
     * @return list<array<string, mixed>>
     */
    public function legalPages(?string $locale = null): array
    {
        $pages = [];

        foreach (PolicyType::cases() as $type) {
            try {
                $pages[] = $this->policy($type, $locale);
            } catch (ModelNotFoundException) {
                continue;
            }
        }

        return $pages;
    }

    /**
     * Draft preview: the same payload the public would get, built from the unpublished row (never cached).
     *
     * @return array<string, mixed>
     */
    public function preview(Model $content, string $locale): array
    {
        return match (true) {
            $content instanceof ContentPage => $this->pagePayload($content, $locale, true, true) ?? [],
            $content instanceof ContentArticle => $this->articlePayload($content, $locale, true, true) ?? [],
            $content instanceof ContentFaq => $this->faqPayload($content, $locale, true) ?? [],
            $content instanceof ContentBanner => $this->bannerPayload($content, $locale, true) ?? [],
            $content instanceof ContentMenu => $this->menuPayload($content, $locale, true) ?? [],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pagePayload(ContentPage $page, string $locale, bool $full, bool $preview = false): ?array
    {
        $title = $this->localizer->text($page, 'title', $locale);

        if ($title === null) {
            return null;
        }

        $payload = [
            'id' => $page->uuid,
            'kind' => $page->kind->value,
            'slug' => $page->slug,
            'title' => $title,
            'summary' => $this->localizer->text($page, 'summary', $locale),
            'hero_image' => $this->image($page->hero, $locale),
            'service_category' => $page->serviceCategory === null ? null : ['code' => $page->serviceCategory->code, 'name' => (string) $page->serviceCategory->getTranslation('name', $locale, true)],
            'city' => $page->city === null ? null : ['id' => $page->city->id, 'name' => (string) $page->city->getTranslation('name', $locale, true)],
            'locale' => $this->localizer->servedLocale($page, 'title', $locale),
            'published_at' => $page->published_at?->toIso8601ZuluString(),
            'updated_at' => $page->updated_at?->toIso8601ZuluString(),
        ];

        if (! $full) {
            return $payload;
        }

        $summaryText = (string) $payload['summary'];
        $bodyHtml = $this->localizer->text($page, 'body_html', $locale);

        return $payload + [
            'body_html' => $bodyHtml,
            'blocks' => $this->blocks($page, $locale, $preview),
            'seo' => $this->seo($page, $locale, $title, $summaryText !== '' ? $summaryText : $this->sanitizer->plainText($bodyHtml, 160), $page->ogImage ?? $page->hero),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(ContentPage $page, string $locale, bool $preview): array
    {
        $fallback = $page->fallback_to_ar;
        $blocks = [];

        foreach ((array) $page->blocks as $block) {
            $type = BlockType::tryFrom((string) ($block['type'] ?? ''));
            $title = $this->localizer->pick((array) ($block['title'] ?? []), $locale, $fallback);

            if ($type === null || $title === null) {
                continue;
            }

            $items = [];

            foreach ((array) ($block['items'] ?? []) as $item) {
                $itemTitle = $this->localizer->pick((array) ($item['title'] ?? []), $locale, $fallback);

                if ($itemTitle !== null) {
                    $items[] = ['title' => $itemTitle, 'text' => $this->localizer->pick((array) ($item['text'] ?? []), $locale, $fallback)];
                }
            }

            $label = $this->localizer->pick((array) ($block['cta']['label'] ?? []), $locale, $fallback);
            $url = $block['cta']['url'] ?? null;
            $entry = [
                'type' => $type->value,
                'title' => $title,
                'body_html' => $this->localizer->pick((array) ($block['body_html'] ?? []), $locale, $fallback),
                'items' => $items,
                'cta' => $label !== null && is_string($url) && $url !== '' ? ['label' => $label, 'url' => $url] : null,
            ];

            $blocks[] = $entry + match ($type) {
                BlockType::Faq => ['faqs' => array_slice($this->faqs(is_string($block['ref'] ?? null) ? $block['ref'] : null, null, $locale)['faqs'], 0, 8)],
                BlockType::Cities => ['cities' => $this->cities($locale)],
                BlockType::AppDownload => ['app_links' => $this->appLinks()],
                default => [],
            };
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function articlePayload(ContentArticle $article, string $locale, bool $full, bool $preview = false): ?array
    {
        $title = $this->localizer->text($article, 'title', $locale);

        if ($title === null) {
            return null;
        }

        $summary = $this->localizer->text($article, 'summary', $locale);
        $payload = [
            'id' => $article->uuid,
            'type' => $article->type->value,
            'slug' => $article->slug,
            'title' => $title,
            'summary' => $summary,
            'category' => $article->category === null ? null : ['slug' => $article->category->slug, 'name' => $this->localizer->text($article->category, 'name', $locale)],
            'cover_image' => $this->image($article->cover, $locale),
            'locale' => $this->localizer->servedLocale($article, 'title', $locale),
            'published_at' => $article->published_at?->toIso8601ZuluString(),
            'updated_at' => $article->updated_at?->toIso8601ZuluString(),
        ];

        if (! $full) {
            return $payload;
        }

        $bodyHtml = $this->localizer->text($article, 'body_html', $locale);

        return $payload + [
            'body_html' => $bodyHtml,
            'seo' => $this->seo($article, $locale, $title, $summary ?? $this->sanitizer->plainText($bodyHtml, 160), $article->ogImage ?? $article->cover),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function faqPayload(ContentFaq $faq, string $locale, bool $preview = false): ?array
    {
        $question = $this->localizer->text($faq, 'question', $locale);
        $answer = $this->localizer->text($faq, 'answer_html', $locale);

        if ($question === null || $answer === null) {
            return null;
        }

        return [
            'id' => $faq->uuid,
            'question' => $question,
            'answer_html' => $answer,
            'category_slug' => $faq->category?->slug,
            'service_category_code' => $faq->serviceCategory?->code,
            'locale' => $this->localizer->servedLocale($faq, 'question', $locale),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function bannerPayload(ContentBanner $banner, string $locale, bool $preview = false): ?array
    {
        $title = $this->localizer->text($banner, 'title', $locale);

        if ($title === null) {
            return null;
        }

        $label = $this->localizer->text($banner, 'cta_label', $locale);

        return [
            'id' => $banner->uuid,
            'kind' => $banner->kind->value,
            'placement' => $banner->placement->value,
            'audience' => $banner->audience->value,
            'title' => $title,
            'body' => $this->localizer->text($banner, 'body', $locale),
            'image' => $this->image($banner->media, $locale),
            'cta' => $label !== null && $banner->cta_url !== null ? ['label' => $label, 'url' => $banner->cta_url] : null,
            'starts_at' => $banner->starts_at?->toIso8601ZuluString(),
            'ends_at' => $banner->ends_at?->toIso8601ZuluString(),
            'locale' => $this->localizer->servedLocale($banner, 'title', $locale),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function menuPayload(ContentMenu $menu, string $locale, bool $preview = false): ?array
    {
        $title = $this->localizer->text($menu, 'title', $locale);

        if ($title === null) {
            return null;
        }

        $items = [];

        foreach ((array) $menu->items as $item) {
            $label = $this->localizer->pick((array) ($item['label'] ?? []), $locale, $menu->fallback_to_ar);

            if ($label !== null && is_string($item['url'] ?? null)) {
                $items[] = ['label' => $label, 'url' => $item['url'], 'new_tab' => (bool) ($item['new_tab'] ?? false)];
            }
        }

        return ['id' => $menu->uuid, 'location' => $menu->location->value, 'title' => $title, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function seo(Model $content, string $locale, string $title, ?string $description, ?ContentMedia $ogImage): array
    {
        $seoTitle = $this->localizer->text($content, 'seo_title', $locale) ?? $title;
        $seoDescription = $this->localizer->text($content, 'seo_description', $locale) ?? $description;

        return [
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $this->localizer->text($content, 'og_title', $locale) ?? $seoTitle,
            'og_description' => $this->localizer->text($content, 'og_description', $locale) ?? $seoDescription,
            'og_image' => $this->image($ogImage, $locale),
            'canonical_url' => $content->getAttribute('canonical_url'),
            'noindex' => (bool) $content->getAttribute('noindex'),
            'available_locales' => array_values(array_filter(
                array_map(static fn (Locale $candidate): string => $candidate->value, Locale::cases()),
                fn (string $candidate): bool => $this->localizer->available($content, 'title', $candidate),
            )),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function image(?ContentMedia $media, string $locale): ?array
    {
        if ($media === null) {
            return null;
        }

        return [
            'url' => $media->url(),
            'alt' => $this->localizer->text($media, 'alt', $locale),
            'width' => $media->width,
            'height' => $media->height,
            'variants' => collect((array) $media->variants)->mapWithKeys(static fn (string $path, string|int $width): array => [(string) $width => $media->url((int) $width)])->all(),
        ];
    }

    private function locale(?string $locale): string
    {
        return Locale::tryFromValue($locale ?? app()->getLocale())->value ?? Locale::default()->value;
    }
}
