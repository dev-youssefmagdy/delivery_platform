<?php

namespace App\DTOs\Content;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Content\ArticleType;
use App\Enums\Content\BannerPlacement;
use App\Enums\Content\CategoryKind;
use App\Enums\Content\ContentWorkflowStatus;
use App\Enums\Content\LocaleCompleteness;
use App\Enums\Content\MenuLocation;
use App\Enums\Content\PageKind;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the CMS listings (Plan Appendix C: type, status, locale completeness, updated range). One DTO serves
 * pages, articles, FAQs, banners, menus and categories; each repository applies the fields that make sense for it.
 */
final readonly class ContentFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<ContentWorkflowStatus> $statuses
     * @param list<PageKind> $pageKinds
     * @param list<ArticleType> $articleTypes
     * @param list<BannerPlacement> $placements
     * @param list<MenuLocation> $locations
     * @param list<int> $categoryIds
     */
    public function __construct(
        public ?string $search = null,
        public array $statuses = [],
        public array $pageKinds = [],
        public array $articleTypes = [],
        public array $placements = [],
        public array $locations = [],
        public array $categoryIds = [],
        public ?LocaleCompleteness $completeness = null,
        public ?CategoryKind $categoryKind = null,
        public ?bool $active = null,
        public ?CarbonImmutable $updatedFrom = null,
        public ?CarbonImmutable $updatedTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$updatedFrom, $updatedTo] = self::dateRangeFilter($input, 'updated_at');
        $completeness = self::enumListFilter($input, 'completeness', LocaleCompleteness::class);
        $kinds = self::enumListFilter($input, 'category_kind', CategoryKind::class);

        return new self(
            search: self::stringFilter($input, 'search'),
            statuses: self::enumListFilter($input, 'statuses', ContentWorkflowStatus::class),
            pageKinds: self::enumListFilter($input, 'kinds', PageKind::class),
            articleTypes: self::enumListFilter($input, 'types', ArticleType::class),
            placements: self::enumListFilter($input, 'placements', BannerPlacement::class),
            locations: self::enumListFilter($input, 'locations', MenuLocation::class),
            categoryIds: self::idListFilter($input, 'category_ids'),
            completeness: $completeness[0] ?? null,
            categoryKind: $kinds[0] ?? null,
            active: self::boolFilter($input, 'active'),
            updatedFrom: $updatedFrom,
            updatedTo: $updatedTo,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->search, $this->statuses, $this->pageKinds, $this->articleTypes, $this->placements, $this->locations,
            $this->categoryIds, $this->completeness, $this->categoryKind, $this->active, $this->updatedFrom ?? $this->updatedTo,
        ]);
    }

    public function toArray(): array
    {
        $values = static fn (array $enums): array => array_map(static fn (\BackedEnum $case): string|int => $case->value, $enums);

        return array_filter([
            'search' => $this->search,
            'statuses' => $values($this->statuses),
            'kinds' => $values($this->pageKinds),
            'types' => $values($this->articleTypes),
            'placements' => $values($this->placements),
            'locations' => $values($this->locations),
            'category_ids' => $this->categoryIds,
            'completeness' => $this->completeness?->value,
            'category_kind' => $this->categoryKind?->value,
            'active' => $this->active,
            'updated_at' => array_filter(['from' => $this->updatedFrom?->toDateString(), 'to' => $this->updatedTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
