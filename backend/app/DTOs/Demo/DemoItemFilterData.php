<?php

namespace App\DTOs\Demo;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\Demo\DemoItemStatus;
use App\Enums\Demo\DemoItemType;
use App\Support\Money\CurrencyRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Filters of the Demo Item listing — one of each filter component: text, enum (multi), enum (single),
 * boolean, remote select, number range (money, decimal input → minor units) and date range.
 */
final readonly class DemoItemFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<DemoItemStatus> $statuses
     * @param list<int> $countryIds
     */
    public function __construct(
        public ?string $search = null,
        public array $statuses = [],
        public ?DemoItemType $type = null,
        public ?bool $featured = null,
        public array $countryIds = [],
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public ?CarbonImmutable $createdFrom = null,
        public ?CarbonImmutable $createdTo = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);
        [$createdFrom, $createdTo] = self::dateRangeFilter($input, 'created_at');
        $price = is_array($input['price'] ?? null) ? $input['price'] : [];
        $exponent = self::exponent();

        return new self(
            search: self::stringFilter($input, 'search'),
            statuses: self::enumListFilter($input, 'statuses', DemoItemStatus::class),
            type: self::enumListFilter($input, 'type', DemoItemType::class)[0] ?? null,
            featured: self::boolFilter($input, 'featured'),
            countryIds: self::idListFilter($input, 'country'),
            priceMin: self::minorUnitsFilter($price, 'min', $exponent),
            priceMax: self::minorUnitsFilter($price, 'max', $exponent),
            createdFrom: $createdFrom,
            createdTo: $createdTo,
        );
    }

    public function activeCount(): int
    {
        return self::countActive([
            $this->search,
            $this->statuses,
            $this->type,
            $this->featured,
            $this->countryIds,
            $this->priceMin ?? $this->priceMax,
            $this->createdFrom ?? $this->createdTo,
        ]);
    }

    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'statuses' => array_map(static fn (DemoItemStatus $status): string => $status->value, $this->statuses),
            'type' => $this->type?->value,
            'featured' => $this->featured === null ? null : ($this->featured ? '1' : '0'),
            'country' => $this->countryIds,
            'price' => array_filter([
                'min' => $this->priceMin === null ? null : self::decimalFromMinor($this->priceMin, self::exponent()),
                'max' => $this->priceMax === null ? null : self::decimalFromMinor($this->priceMax, self::exponent()),
            ]),
            'created_at' => array_filter(['from' => $this->createdFrom?->toDateString(), 'to' => $this->createdTo?->toDateString()]),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    private static function exponent(): int
    {
        return app(CurrencyRegistry::class)->get((string) config('admin.default_currency'))->exponent;
    }
}
