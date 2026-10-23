<?php

namespace App\Services\Content;

use App\Enums\Content\BannerKind;
use App\Enums\Content\PageKind;
use App\Exceptions\BusinessRuleViolation;
use App\Models\ContentArticle;
use App\Models\ContentBanner;
use App\Models\ContentFaq;
use App\Models\ContentMenu;
use App\Models\ContentPage;
use App\Support\Content\Translations;
use Illuminate\Database\Eloquent\Model;

/**
 * Completeness rules checked before content is submitted, scheduled or published: Arabic is always required;
 * English is required only when the item is configured not to fall back to Arabic (`fallback_to_ar` off).
 */
final class ContentPublishGuard
{
    public function assertReady(Model $content): void
    {
        foreach ($this->requiredTranslatable($content) as $attribute) {
            if (! $this->has($content, $attribute, 'ar')) {
                throw BusinessRuleViolation::make('content_arabic_required');
            }

            if (! (bool) $content->getAttribute('fallback_to_ar') && ! $this->has($content, $attribute, 'en')) {
                throw BusinessRuleViolation::make('content_english_required');
            }
        }

        match (true) {
            $content instanceof ContentPage => $this->page($content),
            $content instanceof ContentBanner => $this->banner($content),
            $content instanceof ContentMenu => $this->menu($content),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function requiredTranslatable(Model $content): array
    {
        return match (true) {
            $content instanceof ContentPage => ['title'],
            $content instanceof ContentArticle => ['title', 'body'],
            $content instanceof ContentFaq => ['question', 'answer'],
            $content instanceof ContentBanner, $content instanceof ContentMenu => ['title'],
            default => [],
        };
    }

    private function page(ContentPage $page): void
    {
        if ($page->kind === PageKind::Service && $page->service_category_id === null) {
            throw BusinessRuleViolation::make('content_page_target_required', 'service_category_id');
        }

        if ($page->kind === PageKind::City && $page->city_id === null) {
            throw BusinessRuleViolation::make('content_page_target_required', 'city_id');
        }

        if ($page->kind === PageKind::Home) {
            if (($page->blocks ?? []) === []) {
                throw BusinessRuleViolation::make('content_page_empty');
            }

            return;
        }

        if (! $this->has($page, 'body', 'ar') && ($page->blocks ?? []) === []) {
            throw BusinessRuleViolation::make('content_page_empty');
        }
    }

    private function banner(ContentBanner $banner): void
    {
        if ($banner->kind === BannerKind::Banner && $banner->media_id === null) {
            throw BusinessRuleViolation::make('content_banner_image_required', 'media_id');
        }

        if ($banner->kind === BannerKind::Announcement && ! $this->has($banner, 'body', 'ar')) {
            throw BusinessRuleViolation::make('content_arabic_required', 'body');
        }

        if ($banner->starts_at !== null && $banner->ends_at !== null && $banner->ends_at->lte($banner->starts_at)) {
            throw BusinessRuleViolation::make('content_banner_window_invalid', 'ends_at');
        }
    }

    private function menu(ContentMenu $menu): void
    {
        $items = (array) $menu->items;

        if ($items === []) {
            throw BusinessRuleViolation::make('content_menu_empty');
        }

        foreach ($items as $item) {
            $label = (array) ($item['label'] ?? []);

            if (trim((string) ($label['ar'] ?? '')) === '') {
                throw BusinessRuleViolation::make('content_arabic_required', 'items');
            }

            if (! $menu->fallback_to_ar && trim((string) ($label['en'] ?? '')) === '') {
                throw BusinessRuleViolation::make('content_english_required', 'items');
            }
        }
    }

    private function has(Model $content, string $attribute, string $locale): bool
    {
        return Translations::exact($content, $attribute, $locale) !== '';
    }
}
