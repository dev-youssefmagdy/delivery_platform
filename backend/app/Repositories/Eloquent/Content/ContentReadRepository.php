<?php

namespace App\Repositories\Eloquent\Content;

use App\Enums\Content\ArticleType;
use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerPlacement;
use App\Enums\Content\CategoryKind;
use App\Enums\Content\PageKind;
use App\Models\City;
use App\Models\ContentAppLink;
use App\Models\ContentArticle;
use App\Models\ContentBanner;
use App\Models\ContentCategory;
use App\Models\ContentFaq;
use App\Models\ContentMenu;
use App\Models\ContentPage;
use App\Repositories\Contracts\Content\ContentReadRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ContentReadRepository implements ContentReadRepositoryInterface
{
    public function livePage(string $slug): ?ContentPage
    {
        return ContentPage::query()->live()->with(['hero', 'ogImage', 'serviceCategory', 'city'])->where('slug', $slug)->first();
    }

    public function livePages(PageKind $kind): Collection
    {
        return ContentPage::query()->live()->with(['hero', 'serviceCategory', 'city'])->where('kind', $kind->value)->orderBy('id')->get();
    }

    public function liveBanners(array $placements, ?BannerAudience $audience, CarbonInterface $now): Collection
    {
        return ContentBanner::query()
            ->live()
            ->with('media')
            ->when($placements !== [], static fn (Builder $query) => $query->whereIn('placement', array_map(static fn (BannerPlacement $placement): string => $placement->value, $placements)))
            ->when($audience !== null, static fn (Builder $query) => $query->whereIn('audience', [BannerAudience::All->value, $audience?->value]))
            ->where(static fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(static fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function liveFaqs(?string $categorySlug, ?string $serviceCategoryCode): Collection
    {
        return ContentFaq::query()
            ->live()
            ->with(['category', 'serviceCategory'])
            ->when($categorySlug !== null, static fn (Builder $query) => $query->whereHas('category', static fn (Builder $inner) => $inner->where('slug', $categorySlug)))
            ->when($serviceCategoryCode !== null, static fn (Builder $query) => $query->whereHas('serviceCategory', static fn (Builder $inner) => $inner->where('code', $serviceCategoryCode)))
            ->where(static fn (Builder $query) => $query->whereNull('category_id')->orWhereHas('category', static fn (Builder $inner) => $inner->where('active', true)))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function activeCategories(CategoryKind $kind): Collection
    {
        return ContentCategory::query()->where('kind', $kind->value)->where('active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function liveArticles(?ArticleType $type, ?string $categorySlug, int $limit): Collection
    {
        return ContentArticle::query()
            ->live()
            ->with(['category', 'cover'])
            ->when($type !== null, static fn (Builder $query) => $query->where('type', $type?->value))
            ->when($categorySlug !== null, static fn (Builder $query) => $query->whereHas('category', static fn (Builder $inner) => $inner->where('slug', $categorySlug)))
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function liveArticle(string $slug): ?ContentArticle
    {
        return ContentArticle::query()->live()->with(['category', 'cover', 'ogImage'])->where('slug', $slug)->first();
    }

    public function liveMenus(): Collection
    {
        return ContentMenu::query()->live()->orderBy('location')->get();
    }

    public function activeAppLinks(): Collection
    {
        return ContentAppLink::query()->where('active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function activeCities(): Collection
    {
        return City::query()->where('active', true)->orderBy('id')->get();
    }
}
