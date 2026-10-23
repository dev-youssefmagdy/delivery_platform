<?php

namespace App\Repositories\Contracts\Content;

use App\Enums\Content\ArticleType;
use App\Enums\Content\BannerAudience;
use App\Enums\Content\BannerPlacement;
use App\Enums\Content\CategoryKind;
use App\Enums\Content\PageKind;
use App\Models\City;
use App\Models\ContentArticle;
use App\Models\ContentPage;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read side of the CMS: only published content (and active links / categories). Used by ContentReadService, the one
 * source for the public website (P5.5) and the apps.
 */
interface ContentReadRepositoryInterface
{
    public function livePage(string $slug): ?ContentPage;

    /**
     * @return Collection<int, ContentPage>
     */
    public function livePages(PageKind $kind): Collection;

    /**
     * Published banners of the placements whose window contains $now, for the audience (plus "all").
     *
     * @param list<BannerPlacement> $placements
     * @return Collection<int, \App\Models\ContentBanner>
     */
    public function liveBanners(array $placements, ?BannerAudience $audience, CarbonInterface $now): Collection;

    /**
     * @return Collection<int, \App\Models\ContentFaq>
     */
    public function liveFaqs(?string $categorySlug, ?string $serviceCategoryCode): Collection;

    /**
     * @return Collection<int, \App\Models\ContentCategory>
     */
    public function activeCategories(CategoryKind $kind): Collection;

    /**
     * @return Collection<int, ContentArticle>
     */
    public function liveArticles(?ArticleType $type, ?string $categorySlug, int $limit): Collection;

    public function liveArticle(string $slug): ?ContentArticle;

    /**
     * @return Collection<int, \App\Models\ContentMenu>
     */
    public function liveMenus(): Collection;

    /**
     * @return Collection<int, \App\Models\ContentAppLink>
     */
    public function activeAppLinks(): Collection;

    /**
     * Active cities (for the "cities" block and the cities page).
     *
     * @return Collection<int, City>
     */
    public function activeCities(): Collection;
}
